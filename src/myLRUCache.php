<?php

/**
 * myLRUCache
 * @author Phil Picton
 * @license MIT (see the LICENSE file for details)
 *
 */
class Cache
{
    public function __construct()
    {
        $this->hashtable = new Memcached('lru');
        $this->hashtable->addServer("localhost", 11211);
        $this->linkedlist = new Memcached('lru');
        $this->linkedlist->addServer("localhost", 11211);
    }

    /**
     * clear memcached
     *
     */
    public function init()
    {
        $this->linkedlist->flush(0);
        $this->hashtable->flush(0);
    }

    /**
     * Get a value with the given key
     * @param string $key the key of the value to be retrieved
     * @return mixed the value to be retrieved
     */
    public function get($key)
    {
        if (!$nodehash = $this->hashtable->get($key)) {
            return null; // not found
        }
        if ($node = $this->linkedlist->get($nodehash)) {
            // move to head of list
            $this->detach($node);
            $node = $this->attach($this->hashtable->get('headkey'), $node);
            //save
            $this->linkedlist->set($nodehash, $node);
            return $node->getValue();
        }
    }

    /**
     * Inserts a new element into the cache
     * @param string $key the key of the new element
     * @param string $value the content of the new element
     */
    public function set($key, $value)
    {
        //check if node exists, if so update and move to head
        if ($nodehash=$this->hashtable->get($key)) {
            $node = $this->linkedlist->get($nodehash);
            $node->setValue($value);
            $this->update($node);
        } else {
            // create new and move to head
            $node = new Node($key, $value);
            $this->create($node);
            // then check if cache is full, if so evict tail
            if ($this->hashtable->get('counter') > 10) {
                $tail = $this->linkedlist->get($this->hashtable->get('tailkey'));
                $this->evict($tail);
            }
        }
        return true;
    }

    /**
     * Attach a node to the head of the list
     * @param string $headhash the hash of the head of the list
     * @param Node $node the node to move to the head of the list
     * @return Node $node the node
     */
    private function attach($headhash, $node)
    {
        //get the things
        $key = $node->getKey();
        $nodehash = $this->hashtable->get($key);// get node's hash
        $oldhead = $this->linkedlist->get($headhash); // get old head node
        //set the things
        $oldhead->setNext($nodehash);//old head's next = new head
        $node->setPrevious($headhash);// new heads prev = old head
        //save
        $this->linkedlist->set($headhash, $oldhead);
        $this->hashtable->set('headkey', $nodehash);//update pointer
        return $node;
    }

    /**
     * Detach a node from the list
     * @param Node $node the node to detach from the list
     */
    private function detach($node)
    {
        //get the two adjacent nodes
        $nexthash = $node->getNext();
        $previoushash = $node->getPrevious();
        $next = $this->linkedlist->get($nexthash);
        $previous = $this->linkedlist->get($previoushash);
        // point them at each other (if they exist)
        if ($next) {
            $next->setPrevious($previoushash);
            $this->linkedlist->set($nexthash, $next);
        }
        if ($previous) {
            $previous->setNext($nexthash);
            $this->linkedlist->set($previoushash, $previous);
        }
    }

    /**
     * Update an existing node and move to head
     * @param Node $node the node to update
     */
    private function update($node)
    {
        $this->detach($node);
        $headhash = $this->hashtable->get('headkey');
        $node = $this->attach($headhash, $node);
        $nodehash = $this->hashtable->get($node->getKey());
        $this->linkedlist->set($nodehash, $node);
    }

    /**
     * Create new node and move to head
     * @param Node $node the node to update
     */
    private function create($node)
    {
        $key = $node->getKey();
        $nodehash = md5($key);
        $this->hashtable->set($key, $nodehash);
        if (!$headhash = $this->hashtable->get('headkey')) {
            $this->hashtable->set('headkey', $nodehash);
            $this->hashtable->set('tailkey', $nodehash);

            $this->linkedlist->set($nodehash, $node);
            $this->hashtable->set('counter', 1);
        } else {
            $node = $this->attach($headhash, $node);
            $this->linkedlist->set($nodehash, $node);
            $this->hashtable->increment('counter', 1);
        }
    }

    /**
     * Evict oldest node from hash
     * @param Node $node the node to evict
     */
    private function evict($node)
    {
        $tailhash = $this->hashtable->get('tailkey');
        $tailnode = $this->linkedlist->get($tailhash);
        $nexthash = $tailnode->getNext();
        $this->hashtable->set('tailkey', $nexthash);
        $this->hashtable->decrement('counter', 1);
        $tailkey = $tailnode->getKey();

        $this->linkedlist->delete($tailhash);
        $this->hashtable->delete($tailkey);
    }
}

class Node
{
    //key of node
    private $key;

    // the content of the node
    private $value;

    // the next node hash
    private $next;

    // the previous node hash
    private $previous;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
    * Sets a new value for the node value
    * @param string the new content of the node
    */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Sets a node as the next node
     * @param string $next the next node hash
     */
    public function setNext($next)
    {
        $this->next = $next;
    }

    /**
     * Sets a node as the previous node
     * @param string $previous the previous node hash
     */
    public function setPrevious($previous)
    {
        $this->previous = $previous;
    }

    /**
     * Returns the node key
     * @return string the key of the node
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the node value
     * @return mixed the content of the node
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the next node
     * @return string the hash of the next node of the node
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Returns the previous node
     * @return string the hash of the previous node of the node
     */
    public function getPrevious()
    {
        return $this->previous;
    }
};
