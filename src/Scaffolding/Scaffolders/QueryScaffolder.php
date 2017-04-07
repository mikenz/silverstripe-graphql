<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GraphQL\Type\Definition\Type;

/**
 * Scaffolds a GraphQL query field.
 */
class QueryScaffolder extends OperationScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{
    /**
     * @var bool
     */
    protected $usePagination = true;

    /**
     * @var array
     */
    protected $sortableFields = [];

    /**
     * @param bool $bool
     */
    public function setUsePagination($bool)
    {
        $this->usePagination = (bool) $bool;

        return $this;
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $manager->addQuery(
            $this->scaffold($manager),
            $this->getName()
        );
    }

    /**
     * @param array $fields
     */
    public function addSortableFields($fields)
    {
        $this->sortableFields = array_unique(
            array_merge(
                $this->sortableFields,
                (array) $fields
            )
        );

        return $this;
    }

    /**
     * Configure the query from an array
     * @param  array  $config
     * @return $this
     */
    public function applyConfig(array $config)
    {
        parent::applyConfig($config);
        if (isset($config['sortableFields'])) {
            if (is_array($config['sortableFields'])) {
                $this->addSortableFields($config['sortableFields']);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'sortableFields must be an array (see %s)',
                    $this->typeName
                ));
            }
        }
        if (isset($config['paginate'])) {
            $this->setUsePagination((bool) $config['paginate']);
        }

        return $this;
    }

    /**
     * @param Manager $manager
     *
     * @return array
     */
    public function scaffold(Manager $manager)
    {             
        if ($this->usePagination && $this->isListScope()) {
            return (new PaginationScaffolder(
                $manager,
                $this->createConnection($manager)
            ))->toArray();
        }

        $typeGetter = $this->createTypeGetter($manager);

        return [
            'name' => $this->operationName,
            'args' => $this->createArgs($manager),
            'type' => $this->createTypeGetter($manager),
            'resolve' => $this->createResolverFunction(),
        ];
    }

    /**
     * Creates a Connection for pagination.
     *
     * @return Connection
     */
    protected function createConnection(Manager $manager)
    {
        $typeName = $this->typeName;

        return Connection::create($this->operationName)
            ->setConnectionType($this->createBaseTypeGetter($manager))
            ->setConnectionResolver($this->createResolverFunction())
            ->setArgs($this->createArgs($manager))
            ->setSortableFields($this->sortableFields);
    }
}
