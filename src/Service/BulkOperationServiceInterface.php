<?php

namespace Drupal\bitly_links\Service;


interface BulkOperationServiceInterface
{
    /**
     * @param $contentType
     *  The content type machine name
     * @param $operation
     *  The bulk operation being executed
     * @param $nodes
     *  Array of node ids
     * @throws \Drupal\Core\Entity\EntityStorageException
     *  This exception is thrown if there is a problem saving the updated node
     */
    public function generate($contentType, $operation, $nodes = array());
}