<?php

return array(
    'Model\Article' => array(
        'useBatchInsert' => true,
        'collection' => 'articles',
        'fields' => array(
            'title'    => 'string',
            'content'  => 'string',
            'note'     => 'string',
            'line'     => 'string',
            'text'     => 'string',
            'isActive' => 'boolean',
            'date'     => 'date',
            'database' => array('dbName' => 'basatos', 'type' => 'string'),
        ),
        'embeddedsOne' => array(
            'source'          => array('class' => 'Model\Source'),
            'simpleEmbedded' => array('class' => 'Model\SimpleEmbedded'),
        ),
        'embeddedsMany' => array(
            'comments' => array('class' => 'Model\Comment'),
        ),
        'referencesOne' => array(
            'author'      => array('class' => 'Model\Author', 'field' => 'authorId', 'onDelete' => 'cascade'),
            'information' => array('class' => 'Model\ArticleInformation', 'field' => 'informationId', 'onDelete' => 'unset'),
            'like'        => array('polymorphic' => true, 'field' => 'likeRef', 'onDelete' => 'cascade'),
            'likeUnset'   => array('polymorphic' => true, 'onDelete' => 'unset'),
            'friend'      => array('polymorphic' => true, 'field' => 'friendRef', 'onDelete' => 'cascade', 'discriminatorField' => 'name', 'discriminatorMap' => array(
                'au' => 'Model\Author',
                'ct' => 'Model\Category',
                'us' => 'Model\User',
            )),
            'friendUnset' => array('polymorphic' => true, 'onDelete' => 'unset', 'discriminatorField' => 'name', 'discriminatorMap' => array(
                'au' => 'Model\Author',
                'ct' => 'Model\Category',
                'us' => 'Model\User',
            )),
        ),
        'referencesMany' => array(
            'categories' => array('class' => 'Model\Category', 'field' => 'categoryIds', 'onDelete' => 'unset'),
            'related'    => array('polymorphic' => true, 'field' => 'relatedRef', 'onDelete' => 'unset'),
            'elements'   => array('polymorphic' => true, 'field' => 'elementsRef', 'discriminatorField' => 'type', 'discriminatorMap' => array(
                'element'  => 'Model\FormElement',
                'textarea' => 'Model\TextareaFormElement',
                'radio'    => 'Model\RadioFormElement',
            )),
        ),
        'relationsManyThrough' => array(
            'votesUsers' => array('class' => 'Model\User', 'through' => 'Model\ArticleVote', 'local' => 'article', 'foreign' => 'user'),
        ),
        'indexes' => array(
            array(
                'keys'    => array('slug' => 1),
                'options' => array('unique' => true),
            ),
            array(
                'keys' => array('authorId' => 1, 'isActive' => 1),
            )
        ),
    ),
    'Model\ArticleInformation' => array(
        'fields' => array(
            'name' => 'string',
        ),
        'relationsOne' => array(
            'article' => array('class' => 'Model\Article', 'reference' => 'information'),
        ),
    ),
    'Model\ArticleVote' => array(
        'fields' => array(
        ),
        'referencesOne' => array(
            'article' => array('class' => 'Model\Article', 'field' => 'articleId'),
            'user'    => array('class' => 'Model\User', 'field' => 'userId'),
        ),
    ),
    'Model\Author' => array(
        'eventPattern' => 'author.%s',
        'fields' => array(
            'name' => 'string',
        ),
        'relationsManyOne' => array(
            'articles' => array('class' => 'Model\Article', 'reference' => 'author'),
        ),
    ),
    'Model\Category' => array(
        'fields' => array(
            'name' => 'string',
        ),
        'relationsManyMany' => array(
            'articles' => array('class' => 'Model\Article', 'reference' => 'categories'),
        ),
    ),
    'Model\Comment' => array(
        'isEmbedded' => true,
        'fields' => array(
            'name' => 'string',
            'text' => 'string',
            'note' => 'string',
            'line' => 'string',
            'date' => 'date',
        ),
        'referencesOne' => array(
            'author' => array('class' => 'Model\Author', 'field' => 'authorId'),
        ),
        'referencesMany' => array(
            'categories' => array('class' => 'Model\Category', 'field' => 'categoryIds'),
        ),
        'embeddedsMany' => array(
            'infos' => array('class' => 'Model\Info'),
        ),
        'indexes' => array(
            array(
                'keys'    => array('line' => 1),
                'options' => array('unique' => true),
            ),
            array(
                'keys' => array('authorId' => 1, 'note' => 1),
            ),
        ),
    ),
    'Model\Info' => array(
        'isEmbedded' => true,
        'fields' => array(
            'name' => 'string',
            'text' => 'string',
            'note' => 'string',
            'line' => 'string',
        ),
        'indexes' => array(
            array(
                'keys'    => array('note' => 1),
                'options' => array('unique' => true),
            ),
            array(
                'keys' => array('name' => 1, 'line' => 1),
            ),
        ),
    ),
    'Model\Source' => array(
        'isEmbedded' => true,
        'fields' => array(
            'name' => 'string',
            'text' => 'string',
            'note' => 'string',
            'line' => 'string',
            'from' => array('dbName' => 'desde', 'type' => 'string'),
        ),
        'referencesOne' => array(
            'author' => array('class' => 'Model\Author', 'field' => 'authorId'),
        ),
        'referencesMany' => array(
            'categories' => array('class' => 'Model\Category', 'field' => 'categoryIds'),
        ),
        'embeddedsOne' => array(
            'info' => array('class' => 'Model\Info'),
        ),
        'indexes' => array(
            array(
                'keys'    => array('name' => 1),
                'options' => array('unique' => true),
            ),
            array(
                'keys' => array('authorId' => 1, 'line' => 1),
            ),
        ),
    ),
    'Model\User' => array(
        'fields' => array(
            'username' => 'string',
        ),
    ),
    'Model\SimpleEmbedded' => array(
        'isEmbedded' => true,
        'fields' => array(
            'name' => 'string',
        ),
    ),
    // reference to same class
    'Model\Message' => array(
        'fields' => array(
            'author' => 'string',
            'text' => 'string'
        ),
        'referencesOne' => array(
            'replyTo' => array('class' => 'Model\Message', 'field' => 'replyToId'),
        ),
        'indexes' => array(
            array(
                'keys'    => array(
                    'author' => 'text',
                    'text' => 'text',
                ),
                'options' => array(
                    'name' => 'ExampleTextIndex',
                    'weights' => array(
                        'author' => 100,
                        'text' => 30,
                    )
                ),
            ),
        ),
    ),
    // default values
    'Model\Book' => array(
        'fields' => array(
            'title'   => 'string',
            'comment' => array('type' => 'string', 'default' => 'good'),
            'isHere'  => array('type' => 'boolean', 'default' => true),
        ),
    ),
    'Model\InitializeArgs' => array(
        'fields' => array(
            'name' => 'string',
        ),
        'referencesOne' => array(
            'author' => array('class' => 'Model\Author'),
        ),
    ),
    // gridfs
    'Model\Image' => array(
        'isFile' => true,
        'fields' => array(
            'name' => 'string',
        ),
    ),
    // global connection
    'Model\ConnectionGlobal' => array(
        'connection' => 'global',
        'fields' => array(
            'field' => 'string',
        ),
    ),
    // single inheritance
    'Model\Element' => array(
        'inheritable' => array('type' => 'single'),
        'fields' => array(
          'label'   => 'string',
        ),
        'referencesMany' => array(
            'categories' => array('class' => 'Model\Category'),
        ),
        'embeddedsOne' => array(
            'source' => array('class' => 'Model\Source'),
        ),
    ),
    'Model\TextElement' => array(
        'inheritable' => array('type' => 'single'),
        'inheritance' => array('class' => 'Model\Element', 'value' => 'textelement'),
        'fields' => array(
          'htmltext'   => 'string',
        ),
    ),
    'Model\TextTextElement' => array(
        'inheritance' => array('class' => 'Model\TextElement', 'value' => 'texttextelement'),
        'fields' => array(
            'text_text' => 'string',
        ),
    ),
    'Model\FormElement' => array(
        'inheritable' => array('type' => 'single'),
        'inheritance' => array('class' => 'Model\Element', 'value' => 'formelement'),
        'fields' => array(
            'default' => 'raw',
        ),
        'referencesOne' => array(
            'author' => array('class' => 'Model\Author'),
        ),
        'embeddedsMany' => array(
            'comments' => array('class' => 'Model\Comment'),
        ),
    ),
    'Model\TextareaFormElement' => array(
        'inheritance' => array('class' => 'Model\FormElement', 'value' => 'textarea'),
        'fields' => array(
            'default' => 'string',
        ),
    ),
    'Model\RadioFormElement' => array(
        'inheritance' => array('class' => 'Model\FormElement', 'value' => 'radio'),
        'fields' => array(
            'options' => 'serialized',
        ),
        'referencesOne' => array(
            'authorLocal' => array('class' => 'Model\Author'),
        ),
        'referencesMany' => array(
            'categoriesLocal' => array('class' => 'Model\Category'),
        ),
        'embeddedsOne' => array(
            'sourceLocal' => array('class' => 'Model\Source'),
        ),
        'embeddedsMany' => array(
            'commentsLocal' => array('class' => 'Model\Comment'),
        ),
    ),
    // id generators
    'Model\NoneIdGenerator' => array(
        'idGenerator' => 'none',
        'fields' => array(
            'name' => 'string',
        )
    ),
    'Model\NativeIdGenerator' => array(
        'idGenerator' => 'native',
        'fields' => array(
            'name' => 'string',
        )
    ),
    'Model\SequenceIdGenerator' => array(
        'idGenerator' => 'sequence',
        'fields' => array(
            'name' => 'string',
        )
    ),
    'Model\SequenceIdGenerator2' => array(
        'idGenerator' => 'sequence',
        'fields' => array(
            'name' => 'string',
        )
    ),
    'Model\SequenceIdGeneratorDescending' => array(
        'idGenerator' => array('name' => 'sequence', 'options' => array('increment' => -1)),
        'fields' => array(
            'name' => 'string',
        )
    ),
    'Model\SequenceIdGeneratorStart' => array(
        'idGenerator' => array('name' => 'sequence', 'options' => array('start' => 2000)),
        'fields' => array(
            'name' => 'string',
        )
    ),
    'Model\IdGeneratorSingleInheritanceGrandParent' => array(
        'inheritable' => array('type' => 'single'),
        'idGenerator' => 'sequence',
        'fields' => array(
            'name' => 'string',
        ),
    ),
    'Model\IdGeneratorSingleInheritanceParent' => array(
        'inheritance' => array('class' => 'Model\IdGeneratorSingleInheritanceGrandParent', 'value' => 'parent'),
        'inheritable' => array('type' => 'single'),
    ),
    'Model\IdGeneratorSingleInheritanceChild' => array(
        'inheritance' => array('class' => 'Model\IdGeneratorSingleInheritanceParent', 'value' => 'child'),
    ),

    'Model\ABIdGenerator' => array(
        'idGenerator' => 'ab-id',
        'fields' => array(
            'name' => 'string',
        )
    ),

    'Model\Cached' => array(
        'useBatchInsert' => true,
        'collection' => 'cached',
        'cache' => array(
            'ttl' => 1
        ),
        'fields' => array(
            'title'    => 'string',
            'content'  => 'string',
            'note'     => 'string',
            'line'     => 'string',
            'text'     => 'string',
            'isActive' => 'boolean',
            'date'     => 'date',
            'database' => array('dbName' => 'basatos', 'type' => 'string'),
        )
    ),

    'Model\FieldTypeExamples' => array(
        'fields' => array(
            'name' => 'string',
            'position' => array('dbName' => 'pos', 'type' => 'integer'),
            'avg' => 'float',
            'date' => 'date',
            'isActive' => 'boolean',
            'bindata' => 'bin_data',
            'rawdata' => 'raw',
            'serializeddata' => 'serialized',
        ),
        'referencesOne' => array(
            'author'      => array('class' => 'Model\Author'),
        ),
        'referencesMany' => array(
            'categories' => array('class' => 'Model\Category'),
        ),
    ),

    'Model\CircularReference' => array(
        'fields' => array(
            'value' => 'integer',
        ),
        'referencesOne' => array(
            'other' => array('class' => 'Model\CircularReference'),
        ),
    ),

    'Model\SimpleDocument' => array(
        'collection' => 'simple',
        'fields' => array(
            'string' => 'string',
            'float'  => 'float',
            'int'    => 'string',
            'field4' => 'string',
            'field5' => 'string',
            'field6' => 'string',
            'field7' => 'string',
            'field8' => 'string',
            'field9' => 'string'
        ),
        'embeddedsMany' => array(
            'nested' => array('class' => 'Model\SimpleEmbedded'),
        )
    ),

    'Model\SimpleEmbedded' => array(
        'isEmbedded' => true,
        'fields' => array(
            'name' => 'string',
            'string' => 'string',
            'float'  => 'float',
            'int'    => 'string',
            'field4' => 'string',
            'field5' => 'string',
            'field6' => 'string',
            'field7' => 'string',
            'field8' => 'string',
            'field9' => 'string'
        )
    ),

    'Model\ComplexDocument' => array(
        'collection' => 'complex',
        'fields' => array(
            'string' => 'string',
            'float'  => 'float',
            'int'    => 'string',
            'field4' => 'string',
            'field5' => 'string',
            'field6' => 'string',
            'field7' => 'string',
            'field8' => 'string',
            'field9' => 'string',
            'date' => 'date',
            'bin' => 'raw'
        ),
        'embeddedsMany' => array(
            'nested' => array('class' => 'Model\ComplexEmbedded'),
        ),
        'referencesOne' => array(
            'referencesOne' => array('class' => 'Model\SimpleDocument'),
        ),
        'referencesMany' => array(
            'referencesMany' => array('class' => 'Model\SimpleDocument'),
        ),
    ),

    'Model\ComplexEmbedded' => array(
        'isEmbedded' => true,
        'fields' => array(
            'string' => 'string',
            'float'  => 'float',
            'int'    => 'string',
            'field4' => 'string',
            'field5' => 'string',
            'field6' => 'string',
            'field7' => 'string',
            'field8' => 'string',
            'field9' => 'string',
            'date' => 'date',
            'bin' => 'raw'
        ),
        'referencesOne' => array(
            'referencesOne' => array('class' => 'Model\SimpleDocument'),
        ),
        'referencesMany' => array(
            'referencesMany' => array('class' => 'Model\SimpleDocument'),
        ),
    ),

    // events
    'Model\Events' => array(
        'fields' => array(
            'name' => 'string',
        ),
        'events' => array(
            'preInsert'  => array('myPreInsert'),
            'postInsert' => array('myPostInsert'),
            'preUpdate'  => array('myPreUpdate'),
            'postUpdate' => array('myPostUpdate'),
            'preDelete'  => array('myPreDelete'),
            'postDelete' => array('myPostDelete'),
        ),
    ),
);
