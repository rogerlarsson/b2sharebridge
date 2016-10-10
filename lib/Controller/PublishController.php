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

namespace OCA\B2shareBridge\Controller;

use OC\Files\Filesystem;
use OCA\B2shareBridge\Db\DepositStatus;
use OCA\B2shareBridge\Db\DepositStatusMapper;
use OCA\B2shareBridge\Db\StatusCodeMapper;
use OCA\B2shareBridge\Job\TransferHandler;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Util;

/**
 * Implement a ownCloud AppFramework Controller
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class PublishController extends Controller
{
    private $_appName;
    private $_userId;
    private $_statusCodes;
    private $_lastGoodStatusCode = 2;

    /**
     * Creates the AppFramwork Controller
     *
     * @param string              $appName  name of the app
     * @param IRequest            $request  request object
     * @param IConfig             $config   config object
     * @param DepositStatusMapper $mapper   whatever
     * @param StatusCodeMapper    $scMapper whatever
     * @param string              $userId   userid
     */
    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        DepositStatusMapper $mapper,
        StatusCodeMapper $scMapper,
        $userId
    ) {
        parent::__construct($appName, $request);
        $this->_appName = $appName;
        $this->_userId = $userId;
        $this->mapper = $mapper;
        $this->scMapper = $scMapper;
        $this->config = $config;
        $this->_statusCodes = $this->_listStatusCodes();
        $this->_lastGoodStatusCode = array_search('processing', $this->_statusCodes);
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @return array
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    private function _listStatusCodes()
    {
        $statuscodes = [];
        foreach (
            $this->scMapper->findAllStatusCodes()
            as $statuscode) {
            $statuscodes[] = $statuscode->getMessage();
        }
        return $statuscodes;
    }

    /**
     * XHR request endpoint for getting publish command
     *
     * @return          JSONResponse
     * @NoAdminRequired
     */
    public function publish()
    {
        $param = $this->request->getParams();

        $error = false;
        if (!is_array($param)
            || !array_key_exists('id', $param)
            || !array_key_exists('token', $param)
        ) {
            $error = 'Parameters gotten from UI are no array or they are missing';
        }
        $id = (int)$param['id'];
        $token = $param['token'];

        if (!is_int($id) || !is_string($token)) {
            $error = 'Problems while parsing fileid or publishToken';
        }
        $_userId = \OC::$server->getUserSession()->getUser()->getUID();
        if (strlen($_userId) <= 0) {
            $error = 'No user configured for session';
        }
        if (($error)) {
            Util::writeLog('b2sharebridge', $error, 3);
            return new JSONResponse(
                [
                    'message' => 'Internal server error, contact the EUDAT helpdesk',
                    'status' => 'error'
                ]
            );
        }


        $allowed_uploads = $this->config->getAppValue(
            'b2sharebridge',
            'max_uploads',
            5
        );
        $allowed_filesize = $this->config->getAppValue(
            'b2sharebridge',
            'max_upload_filesize',
            5
        );

        $active_uploads = $this->mapper->findCountForUser(
            $_userId, array_search('new', $this->_statusCodes)
        );
        if ($active_uploads < $allowed_uploads) {

            Filesystem::init($_userId, '/');
            $view = Filesystem::getView();
            $filesize = $view->filesize(Filesystem::getPath($id));
            if ($filesize < $allowed_filesize * 1024 * 1024) {
                $job = new TransferHandler($this->mapper);
                $fcStatus = new DepositStatus();
                $fcStatus->setFileid($id);
                $fcStatus->setOwner($_userId);
                $fcStatus->setStatus(1);//status = new
                $fcStatus->setCreatedAt(time());
                $fcStatus->setUpdatedAt(time());
                $this->mapper->insert($fcStatus);
            } else {
                return new JSONResponse(
                    [
                        'message' => 'We currently only support 
                        files smaller then ' . $allowed_filesize . ' MB',
                        'status' => 'error'
                    ]
                );
            }
        } else {
            return new JSONResponse(
                [
                    'message' => 'Until your ' . $active_uploads . ' deposits 
                        are done, you are not allowed to create further deposits.',
                    'status' => 'error'
                ]
            );
        }
        // create the actual transfer Job in the database

        /* TODO: we should add a configuration setting for admins to
         * configure the maximum number of uploads per user and a max filesize.
         *both to avoid DoS
         *
         */

        // register transfer cron
        \OC::$server->getJobList()->add(
            $job, [
                'transferId' => $fcStatus->getId(),
                'token' => $token,
                '_userId' => $_userId
            ]
        );

        return new JSONResponse(
            [
                "message" => 'Transferring file to B2SHARE in the Background',
                'status' => 'success'
            ]
        );
    }
}