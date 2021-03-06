<?php
/**
 * OwnCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Owncloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Model;

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

/**
 * Work on a database table
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class CommunityMapper extends Mapper
{

    /**
     * Create the database mapper
     *
     * @param IDBConnection $db the database connection to use
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct(
            $db,
            'b2sharebridge_communities',
            '\OCA\B2shareBridge\Model\Community'
        );
    }

    /**
     * Return all communities
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return array(Entity)
     */
    public function findAll()
    {
        $sql = 'SELECT * FROM `' . $this->tableName  .'`';
        return $this->findEntities($sql);
    }

    /**
     * Return all communities as aray with id and name
     *
     * @return array(string(id) => string(name))
     */
    public function getCommunityList()
    {
        $communities_b2share = [];
        foreach ($this->findAll() as $community) {
            $communities_b2share[$community->getId()] = $community->getName();
        }
        return $communities_b2share;
    }

    /**
     * Returns Commnuityname by given id. 
     *
     * @param string $uid internal uid of the Community
     * 
     * @return Community
     * @throws ClientNotFoundException
     */
    public function getByUid($uid)
    {
        $qb = $this->db->getQueryBuilder();
        $qb
            ->select('*')
            ->from($this->tableName)
            ->where(
                $qb->expr()->eq(
                    'id', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT)
                )
            );
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        if ($row === false) {
            throw new CommunityNotFoundException();
        }
        return Client::fromRow($row);
    }

    /** 
     * Return all communities as aray with id and name
     * 
     * @param string $id internal id of the Community
     * 
     * @return Communities
     */    
    public function deleteCommunity($id)
    {
        $sql = 'DELETE FROM `' . $this->tableName
            . '` WHERE id = ?';
        return $this->findEntities($sql, [$id]);    
    }

}
