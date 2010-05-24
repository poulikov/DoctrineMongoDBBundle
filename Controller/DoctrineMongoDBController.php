<?php

namespace Bundle\DoctrineMongoDBBundle\Controller;

use Symfony\Framework\WebBundle\Controller;

/**
 * Class that gives shortcuts to the DocumentManager for Doctrine mongodb odm.
 *
 * @package Symfony
 * @subpackage Bundle_DoctrineMongoDDBundle
 * @author Henrik Bjornskov <henrik@bearwoods.dk>
 */
class DoctrineMongoDBController extends Controller
{
    /**
     * Returns the Doctrine ODM DocumentManager
     *
     * @return object
     */
    public function getDocumentManager($name = null)
    {
        if ($name) {
            return $this->container->getService(sprintf('doctrine.odm.%s_document_manager', $name));
        }

        return $this->container->getDoctrine_ODM_DocumentManagerService();
    }
}
