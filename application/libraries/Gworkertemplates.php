<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package     Jagger
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2015, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 * @subpackage  Libraries
 */
class Gworkertemplates
{
    protected $ci;
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;
    protected $digestmethod;
    protected $ispreworkers;
    protected $internalprefixurl;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->digestmethod = $this->ci->config->item('signdigest');
        if (empty($this->digestmethod)) {
            $this->digestmethod = 'SHA-1';
        }

        $this->ispreworkers = $this->ci->config->item('predefinedstats');
        if (empty($this->ispreworkers) || !is_array($this->ispreworkers)) {
            $this->ispreworkers = array();
        }
        $this->internalprefixurl = $this->ci->config->item('internalprefixurl');
        if(!filter_var($this->internalprefixurl, FILTER_VALIDATE_URL)){
            $this->internalprefixurl =  rtrim($this->internalprefixurl, '/') . '/';
        }
        else {
            $this->internalprefixurl = base_url();
        }

    }


    public function resolveTemplate($templateName, array $params) {

        if (strcasecmp($templateName, 'metadatasigner') == 0) {
            return $this->metadatasigner($params);
        }
        if (strcasecmp($templateName, 'statcollector') == 0) {
            return $this->statcollector($params);
        }

        if(strcasecmp($templateName, 'syncentity') == 0){
            return $this->entitySync($params);
        }

        return null;

    }


    private function resolveProvider(\models\Provider $provider, $funcname = null) {
        $digest1 = $provider->getDigest();
        if (empty($digest1)) {
            $digest1 = $this->digestmethod;
        }
        $encodedentity = base64url_encode($provider->getEntityId());
        $sourceurl = $this->internalprefixurl . 'metadata/circle/' . $encodedentity . '/metadata.xml';
        $options = array('src' => '' . $sourceurl . '', 'type' => 'provider', 'encname' => '' . $encodedentity . '', 'digest' => '' . $digest1 . '');
        $result = array(
            'fname'   => $funcname,
            'fparams' => $options
        );

        return $result;

    }

    private function resolveFederation(\models\Federation $fed) {

        $digest1 = $fed->getDigest();
        if (empty($digest1)) {
            $digest1 = $this->digestmethod;
        }
        $digest2 = $fed->getDigestExport();
        if (empty($digest2)) {
            $digest2 = $this->digestmethod;
        }
        $localexport = $fed->getLocalExport();

        $result[] = array(
            'fname'   => 'metadatasigner',
            'fparams' => array(
                'src'     => '' . $this->internalprefixurl . 'metadata/federation/' . $fed->getSysname() . '/metadata.xml',
                'type'    => 'federation',
                'encname' => $fed->getSysname(),
                'digest'  => '' . $digest1 . ''
            )
        );

        if (!empty($localexport)) {
            $result[] = array(
                'fname'   => 'metadatasigner',
                'fparams' => array(
                    'src'     => '' . $this->internalprefixurl . 'metadata/federationexport/' . $fed->getSysname() . '/metadata.xml',
                    'type'    => 'federationexport',
                    'encname' => '' . $fed->getSysname() . '',
                    'digest'  => '' . $digest2 . ''
                )
            );
        }

        return $result;
    }

    /**
     * @param array $params
     * @return null|array
     */
    private function entitySync(array $params){
        $result = array();
        if(isset($params['entityid'])){
            /**
             * @var $ent models\Provider
             */
            $ent = $this->em->getRepository('models\Provider')->findOneBy(array('entityid'=>$params['entityid'],));
            if($ent !== null){
                $result[] = array(
                    'fname'  => 'syncentity',
                    'fparams' => $params
                );
                return $result;
            }
        }
        return null;
    }

    private function statcollector(array $params) {

        if (!array_key_exists('statid', $params) || !ctype_digit($params['statid'])) {

            log_message('error', 'Task scheduler can run stat collector: no/incorrect params provided');

            return false;
        }
        $statid = $params['statid'];
        /**
         * @var models\ProviderStatsDef $statDefinition
         */
        $statDefinition = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $statid));
        if ($statDefinition === null) {
            log_message('error', 'Task scheduler can run stat collector as stadef not found with id:' . $statid);

            return false;
        }
        $provider = $statDefinition->getProvider();
        if ($provider === null) {
            log_message('error', 'Task scheduler can run stat collector as provider not found for stadefid:' . $statid);

            return false;
        }
        $result['fparams'] = $statDefinition->toParamArray();


        if (array_key_exists('type', $result['fparams'])) {
            if ($result['fparams']['type'] === 'ext') {
                $result['fname'] = 'externalstatcollection';
            } elseif (($result['fparams']['type'] === 'sys') && !empty($result['fparams']['sysdef']) && array_key_exists($result['fparams']['sysdef'], $this->ispreworkers) && array_key_exists('worker', $this->ispreworkers['' . $result['fparams']['sysdef'] . '']) && !empty($this->ispreworkers['' . $result['fparams']['sysdef'] . '']['worker'])) {
                $workername = $this->ispreworkers['' . $result['fparams']['sysdef'] . '']['worker'];
                $result['fname'] = $workername;
            }
        }

        if (array_key_exists('fname', $result)) {

            return array($result);
        }

        return false;

    }

    private function metadataSignerBulk(array $params) {
        $result = array();
        if (array_key_exists('name', $params) && !empty($params['name'])) {
            if ($params['name'] === 'all') {
                $federations = $this->em->getRepository("models\Federation")->findAll();
                foreach ($federations as $fed) {
                    $resolvedFed = $this->resolveFederation($fed);
                    foreach ($resolvedFed as $rv) {
                        $result[] = $rv;
                    }
                }
                $providers = $this->em->getRepository("models\Provider")->findBy(array('is_local' => true));
                foreach ($providers as $provider) {
                    $result[] = $this->resolveProvider($provider,'metadatasigner');
                }
            } elseif ($params['name'] === 'federations') {
                $federations = $this->em->getRepository("models\Federation")->findAll();
                foreach ($federations as $fed) {
                    $resolvedFed = $this->resolveFederation($fed);
                    foreach ($resolvedFed as $rv) {
                        $result[] = $rv;
                    }
                }
            } elseif ($params['name'] === 'providers') {
                $providers = $this->em->getRepository("models\Provider")->findBy(array('is_local' => true));
                foreach ($providers as $provider) {
                    $result[] = $this->resolveProvider($provider,'metadatasigner');
                }
            }
        }

        return $result;
    }

    /**
     * @param array $params
     * @return array
     */
    private function metadatasigner(array $params) {

        $result = array();
        if (!array_key_exists('type', $params)) {
            return $result;
        }

        if (array_key_exists('sysname', $params) && $params['type'] === 'federation' && !empty($params['sysname'])) {
            /**
             * @var models\Federation $fed
             */
            $fed = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => '' . $params['sysname'] . ''));
            if (empty($fed)) {
                return null;
            }
            $resolvedFed = $this->resolveFederation($fed);
            foreach ($resolvedFed as $rv) {
                $result[] = $rv;
            }

        } elseif (array_key_exists('entityid', $params) && $params['type'] === 'provider' && !empty($params['entityid'])) {
            /**
             * @var models\Provider $provider
             */
            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $params['entityid'], 'is_local' => '1'));
            if ($provider === null) {
                return null;
            }
            $result[] = $this->resolveProvider($provider, 'metadatasigner');
        } elseif ($params['type'] === 'bulk') {

            $result = $this->metadataSignerBulk($params);

        }


        return $result;

    }

}
