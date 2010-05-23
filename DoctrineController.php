<?php

namespace Bundle\DoctrineMongoDBBundle;

use Symfony\Framework\WebBundle\Controller;

/**
 * Class that gives shortcuts to the DocumentManager for Doctrine mongodb odm.
 *
 * @package Symfony
 * @subpackage Bundle_DoctrineMongoDDBundle
 * @author Henrik Bjornskov <henrik@bearwoods.dk>
 */
class DoctrineController extends Controller
{
    /**
     * Returns the Doctrine ODM DocumentManager
     *
     * @return object
     */
    public function getDocumentManager()
    {
        return $this->container->getDoctrine_ODM_DocumentManagerService();
    }
}
