<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Jusermanage
{
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected  $ci, $em;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
    }

    public function remove(models\User $user)
    {
        $this->em->remove($user);
        /**
         * @var models\AclRole $personalRole
         */
        $personalRole = $this->em->getRepository("models\AclRole")->findOneBy(
            array(
                'type' => 'user',
                'name' => $user->getUsername())
        );
        if ($personalRole !== null) {
            $this->em->remove($personalRole);
        }
        $this->em->flush();
    }

}
