<?php


class Gworkertemplates
{
    protected $ci;
    protected $em;
    protected $digestmethod;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->digestmethod = $this->ci->config->item('signdigest');
        if (empty($this->digestmethod)) {
            $this->digestmethod = 'SHA-1';
        }

    }

    /**
     * @param $templateName
     * @param array $params
     * @return array|null
     */
    public function resolveTemplate($templateName, array $params)
    {

        if (strcasecmp($templateName, 'metadatasigner') == 0) {
            return $this->metadatasigner($params);

        }
        return null;

    }


    private function resolveProvider(\models\Provider $provider)
    {
        $digest1 = $provider->getDigest();
        if (empty($digest1)) {
            $digest1 = $this->digestmethod;
        }
        $encodedentity = base64url_encode($provider->getEntityId());
        $sourceurl = base_url() . 'metadata/circle/' . $encodedentity . '/metadata.xml';
        $options = array('src' => '' . $sourceurl . '', 'type' => 'provider', 'encname' => '' . $encodedentity . '', 'digest' => '' . $digest1 . '');
        $result = array(
            'fname' => 'metadatasigner',
            'fparams' => $options
        );
        return $result;

    }

    private function resolveFederation(\models\Federation $fed)
    {

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
            'fname' => 'metadatasigner',
            'fparams' => array(
                'src' => '' . base_url() . 'metadata/federation/' . $fed->getSysname() . '/metadata.xml',
                'type' => 'federation',
                'encname' => $fed->getSysname(),
                'digest' => '' . $digest1 . ''
            )
        );

        if (!empty($localexport)) {
            $result[] = array(
                'fname' => 'metadatasigner',
                'fparams' => array(
                    'src' => '' . base_url() . 'metadata/federationexport/' . $fed->getSysname() . '/metadata.xml',
                    'type' => 'federationexport',
                    'encname' => '' . $fed->getSysname() . '',
                    'digest' => '' . $digest2 . ''
                )
            );
        }

        return $result;
    }

    private function metadatasigner(array $params)
    {

        $result = array();
        if (array_key_exists('type', $params)) {
            if ($params['type'] === 'federation') {
                if (array_key_exists('sysname', $params) && !empty($params['sysname'])) {
                    $fed = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => '' . $params['sysname'] . ''));
                    if (empty($fed)) {
                        return null;
                    }
                    $r = $this->resolveFederation($fed);
                    foreach ($r as $rv) {
                        $result[] = $rv;
                    }

                }

            } elseif ($params['type'] === 'provider') {
                if (array_key_exists('entityid', $params) && !empty($params['entityid'])) {
                    $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $params['entityid']));
                    if (empty($provider)) {
                        return null;
                    }
                    $is_local = $provider->getLocal();
                    if (!$is_local) {
                        return null;
                    }
                    $result[] = $this->resolveProvider($provider);
                }

            } elseif ($params['type'] === 'bulk') {
                if (array_key_exists('name', $params) && !empty($params['name'])) {
                    if ($params['name'] === 'all') {
                        $federations = $this->em->getRepository("models\Federation")->findAll();
                        foreach ($federations as $fed) {
                            $r = $this->resolveFederation($fed);
                            foreach ($r as $rv) {
                                $result[] = $rv;
                            }
                        }
                        $providers = $this->em->getRepository("models\Provider")->findBy(array('is_local' => true));
                        foreach ($providers as $provider) {
                            $result[] = $this->resolveProvider($provider);
                        }
                    } elseif ($params['name'] === 'federations') {
                        $federations = $this->em->getRepository("models\Federation")->findAll();
                        foreach ($federations as $fed) {
                            $r = $this->resolveFederation($fed);
                            foreach ($r as $rv) {
                                $result[] = $rv;
                            }
                        }
                    } elseif ($params['name'] === 'providers') {
                        $providers = $this->em->getRepository("models\Provider")->findBy(array('is_local' => true));
                        foreach ($providers as $provider) {
                            $result[] = $this->resolveProvider($provider);
                        }
                    }
                }

            }
        }
        return $result;

    }


}