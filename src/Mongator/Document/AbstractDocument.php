<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Document;

use Mongator\Archive;
use Mongator\Mongator;

/**
 * The abstract class for documents.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 * @author Máximo Cuadros <mcuadros@gmail.com>
 *
 * @api
 */ 
abstract class AbstractDocument
{
    private $mongator;
    private $archive;
    public $data = array();
    protected $fieldsModified = array();
    protected $onceEnvents = array();
    protected $savingReferences = false;
    protected $eventPattern;

    /**
     * Constructor.
     *
     * @param Mongator $mongator The Mongator.
     */
    public function __construct(Mongator $mongator)
    {
        $this->setMongator($mongator);

        $this->archive = new Archive();
    }

    /**
     * Return the Archive object
     *
     * @return \Mongator\Archive the archive object from this document
     *
     * @api
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Sleep - prepare the object for serialization
     */
    public function __sleep()
    {
        $rc = new \ReflectionObject($this);

        $names = array();
        $filter = array('Mongator');

        while ($rc instanceof \ReflectionClass) {
            foreach ($rc->getProperties() as $prop) {
                if ( !in_array($prop->getName(), $filter) ) $names[] = $prop->getName();
            }

            $rc = $rc->getParentClass();
        }

        return array_unique($names);
    }

    /**
     * Load the full document from the database, in case some fields were not loaded when
     * it was first read. Previously modified fields are not overwritten.
     *
     * This method updates the "fields in query" information.
     */
    abstract public function loadFull();

    /**
     * Check whether the field $field was recovered in the query that was used to get the data
     * to populate the object.
     *
     * @param  string  $field the field name
     * @return boolean whether the field was present
     */
    abstract public function isFieldInQuery($field);

    /**
     * Set the Mongator.
     *
     * @return Mongator The Mongator.
     */
    public function setMongator(Mongator $mongator)
    {
        return $this->mongator = $mongator;
    }

    /**
     * Returns the Mongator.
     *
     * @return Mongator The Mongator.
     */
    public function getMongator()
    {
        return $this->mongator;
    }

    /**
     * Returns the document metadata.
     *
     * @return array The document metadata.
     */
    public function getMetadata()
    {
        return $this->getMongator()->getMetadataFactory()->getClass(get_class($this));
    }

    /**
     * Returns the document data.
     *
     * @return array The document data.
     */
    public function getDocumentData()
    {
        return $this->data;
    }

    /**
     * Returns if the document is modified.
     *
     * @return bool If the document is modified.
     *
     * @api
     */
    public function isModified()
    {
        if (isset($this->data['fields'])) {
            foreach ($this->data['fields'] as $name => $value) {
                if ($this->isFieldModified($name)) {
                    return true;
                }
            }
        }

        if (isset($this->data['embeddedsOne'])) {
            foreach ($this->data['embeddedsOne'] as $name => $embedded) {
                if ($embedded && $embedded->isModified()) {
                    return true;
                }
                if ($this->isEmbeddedOneChanged($name)) {
                    $root = null;
                    if ($this instanceof Document) {
                        $root = $this;
                    } elseif ($rap = $this->getRootAndPath()) {
                        $root = $rap['root'];
                    }
                    if ($root && !$root->isNew()) {
                        return true;
                    }
                }
            }
        }

        if (isset($this->data['embeddedsMany'])) {
            foreach ($this->data['embeddedsMany'] as $name => $group) {
                $add = $group->getAdd();
                foreach ($add as $document) {
                    if ($document->isModified()) {
                        return true;
                    }
                }
                $root = null;
                if ($this instanceof Document) {
                    $root = $this;
                } elseif ($rap = $this->getRootAndPath()) {
                    $root = $rap['root'];
                }
                if ($root && !$root->isNew()) {
                    if ($group->getRemove()) {
                        return true;
                    }
                }
                if ($group->isSavedInitialized()) {
                    if ($add) return true;
                    foreach ($group->getSaved() as $document) {
                        if ($document->isModified()) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Clear the document modifications, that is, they will not be modifications apart from here.
     *
     * @api
     */
    public function clearModified()
    {
        if (isset($this->data['fields'])) {
            $this->clearFieldsModified();
        }

        if (isset($this->data['embeddedsOne'])) {
            $this->clearEmbeddedsOneChanged();
            foreach ($this->data['embeddedsOne'] as $name => $embedded) {
                if ($embedded) {
                    $embedded->clearModified();
                }
            }
        }

        if (isset($this->data['embeddedsMany'])) {
            foreach ($this->data['embeddedsMany'] as $name => $group) {
                $group->markAllSaved();
            }
        }
    }

    /**
     * Returns if a field is modified.
     *
     * @param string $name The field name.
     *
     * @return bool If the field is modified.
     *
     * @api
     */
    public function isFieldModified($name)
    {
        return isset($this->fieldsModified[$name]) || array_key_exists($name, $this->fieldsModified);
    }

    /**
     * Returns the original value of a field.
     *
     * @param string $name The field name.
     *
     * @return mixed The original value of the field.
     *
     * @api
     */
    public function getOriginalFieldValue($name)
    {
        if ($this->isFieldModified($name)) {
            return $this->fieldsModified[$name];
        }

        if (isset($this->data['fields'][$name])) {
            return $this->data['fields'][$name];
        }

        return null;
    }

    /**
     * Returns an array with the fields modified, the field name as key and the original value as value.
     *
     * @return array An array with the fields modified.
     *
     * @api
     */
    public function getFieldsModified()
    {
        return $this->fieldsModified;
    }

    /**
     * Clear the modifications of fields, that is, they will not be modifications apart from here.
     *
     * @api
     */
    public function clearFieldsModified()
    {
        $this->fieldsModified = array();
    }

    /**
     * Returns if an embedded one is changed.
     *
     * @param string $name The embedded one name.
     *
     * @return bool If the embedded one is modified.
     *
     * @api
     */
    public function isEmbeddedOneChanged($name)
    {
        if (!isset($this->data['embeddedsOne'])) {
            return false;
        }

        if (!isset($this->data['embeddedsOne'][$name]) && !array_key_exists($name, $this->data['embeddedsOne'])) {
            return false;
        }

        return $this->getArchive()->has('embedded_one.'.$name);
    }

    /**
     * Returns the original value of an embedded one.
     *
     * @param string $name The embedded one name.
     *
     * @return mixed The embedded one original value.
     *
     * @api
     */
    public function getOriginalEmbeddedOneValue($name)
    {
        if ($this->getArchive()->has('embedded_one.'.$name)) {
            return $this->getArchive()->get('embedded_one.'.$name);
        }

        if (isset($this->data['embeddedsOne'][$name])) {
            return $this->data['embeddedsOne'][$name];
        }

        return null;
    }

    /**
     * Returns an array with the embedded ones changed, with the embedded name as key and the original embedded value as value.
     *
     * @return array An array with the embedded ones changed.
     *
     * @api
     */
    public function getEmbeddedsOneChanged()
    {
        $embeddedsOneChanged = array();
        if (isset($this->data['embeddedsOne'])) {
            foreach ($this->data['embeddedsOne'] as $name => $embedded) {
                if ($this->isEmbeddedOneChanged($name)) {
                    $embeddedsOneChanged[$name] = $this->getOriginalEmbeddedOneValue($name);
                }
            }
        }

        return $embeddedsOneChanged;
    }

    /**
     * Clear the embedded ones changed, that is, they will not be changed apart from here.
     *
     * @api
     */
    public function clearEmbeddedsOneChanged()
    {
        if (isset($this->data['embeddedsOne'])) {
            foreach ($this->data['embeddedsOne'] as $name => $embedded) {
                $this->getArchive()->remove('embedded_one.'.$name);
            }
        }
    }

    /**
     * Register a callback as preInsert event.
     *
     * @api
     */
    public function registerOncePreInsertEvent($event)
    {
        $this->registerOnceEvent($event, 'pre-insert');
    }

    /**
     * Register a callback as postInsert event.
     *
     * @api
     */
    public function registerOncePostInsertEvent($event)
    {
        $this->registerOnceEvent($event, 'post-insert');
    }

    /**
     * Register a callback as preUpdate event.
     *
     * @api
     */
    public function registerOncePreUpdateEvent($event)
    {
        $this->registerOnceEvent($event, 'pre-update');
    }

    /**
     * Register a callback as postUpdate event.
     *
     * @api
     */
    public function registerOncePostUpdateEvent($event)
    {
        $this->registerOnceEvent($event, 'post-update');
    }

    protected function registerOnceEvent($event, $type)
    {
        if (!is_callable($event)) {
            throw new \Exception('Event parameter must be a callable');
        }

        if (!isset($this->onceEnvents[$type])) {
            $this->onceEnvents[$type] = array();
        }

        $this->onceEnvents[$type][] = $event;
    }

    /**
     * Triggers the pre instert events
     *
     * @api
     */
    public function preInsertEvent()
    {
        $this->oncePreInsertEvent();
        $this->dispatchEvent('pre.insert');
    }

    /**
     * Triggers the post insert events
     *
     * @api
     */
    public function postInsertEvent()
    {
        $this->oncePostInsertEvent();
        $this->dispatchEvent('post.insert');
    }

    /**
     * Triggers the pre update events
     *
     * @api
     */
    public function preUpdateEvent()
    {
        $this->oncePreUpdateEvent();
        $this->dispatchEvent('pre.update');
    }

    /**
     * Triggers the post update events
     *
     * @api
     */
    public function postUpdateEvent()
    {
        $this->oncePostUpdateEvent();
        $this->dispatchEvent('post.update');
    }

    /**
     * Triggers the pre delete events
     *
     * @api
     */
    public function preDeleteEvent()
    {
        $this->dispatchEvent('pre.delete');
    }

    /**
     * Triggers the post delete events
     *
     * @api
     */
    public function postDeleteEvent()
    {
        $this->dispatchEvent('post.delete');
    }

    protected function dispatchEvent($eventName)
    {
        $event = new Event($this);
        $fullEventName = sprintf($this->eventPattern, $eventName);

        $this->mongator->dispatchEvent($fullEventName, $event);
    }

    private function oncePreInsertEvent()
    {
        $this->executeOnceEvent('pre-insert');
    }

    private function oncePostInsertEvent()
    {
        $this->executeOnceEvent('post-insert');
    }

    private function oncePreUpdateEvent()
    {
        $this->executeOnceEvent('pre-update');
    }

    private function oncePostUpdateEvent()
    {
        $this->executeOnceEvent('post-update');
    }

    private function executeOnceEvent($type)
    {
        if (!isset($this->onceEnvents[$type])) {
            return;
        }

        foreach ($this->onceEnvents[$type] as $callback) {
            $callback($this);
        }

        $this->onceEnvents[$type] = array();
    }

    /**
     * Returns an array with the document info to debug.
     *
     * @return array An array with the document info.
     */
    public function debug()
    {
        $info = array();

        $metadata = $this->getMetadata();

        $referenceFields = array();
        foreach (array_merge($metadata['referencesOne'], $metadata['referencesMany']) as $name => $reference) {
            $referenceFields[] = $reference['field'];
        }

        // fields
        foreach ($metadata['fields'] as $name => $field) {
            if (in_array($name, $referenceFields)) {
                continue;
            }
            $info['fields'][$name] = $this->{'get'.ucfirst($name)}();
        }

        // referencesOne
        foreach ($metadata['referencesOne'] as $name => $referenceOne) {
            $info['referencesOne'][$name] = $this->{'get'.ucfirst($referenceOne['field'])}();
        }

        // referencesMany
        foreach ($metadata['referencesMany'] as $name => $referenceMany) {
            $info['referencesMany'][$name] = $this->{'get'.ucfirst($referenceMany['field'])}();
        }

        // embeddedsOne
        foreach ($metadata['embeddedsOne'] as $name => $embeddedOne) {
            $embedded = $this->{'get'.ucfirst($name)}();
            $info['embeddedsOne'][$name] = $embedded ? $embedded->debug() : null;
        }

        // embeddedsMany
        foreach ($metadata['embeddedsMany'] as $name => $embeddedMany) {
            $info['embeddedsMany'][$name] = array();
            foreach ($this->{'get'.ucfirst($name)}() as $key => $value) {
                $info['embeddedsMany'][$name][$key] = $value->debug();
            }
        }

        return $info;
    }
}
