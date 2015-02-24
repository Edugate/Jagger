<?php
namespace models;

use \Doctrine\Common\Collections\ArrayCollection;


/**
 * ResourceRegistry3
 *
 * @package     RR3
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Contact Class
 *
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Contact Model
 *
 * This model for attributes definitions
 *
 * @Entity
 * @Table(name="jcrontab")
 * @author janusz
 */
class Jcrontab
{

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $jminute;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $jhour;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $jdayofmonth;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $jmonth;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $jdayofweek;


    /**
     * @Column(type="string", length=255, nullable = false)
     */
    protected $jcommand;

    /**
     * @Column(type="string", length=512, nullable = false);
     */
    protected $jparams;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $tonotify;


    /**
     * @Column(type="string", length=1024, nullable=false)
     */
    protected $jcomment;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $isenabled;


    function __construct()
    {
        $this->jminute = 1;
        $this->jhour = 8;
        $this->jdayofmonth = '*';
        $this->jmonth = '*';
        $this->jdayofweek = '*';
        $this->tonotify = true;
        $this->jparams = serialize(array());

    }

    public function getId()
    {
        return $this->id;
    }

    public function getEnabled()
    {
        return $this->isenabled;
    }

    public function getMinutes()
    {
        return $this->jminute;
    }

    public function getHours()
    {
        return $this->jhour;
    }

    public function getDaysOfMonth()
    {
        return $this->jdayofmonth;
    }

    public function getMonths()
    {
        return $this->jmonth;
    }

    public function getDaysOfWeek()
    {
        return $this->jdayofweek;
    }

    public function getCronToStr()
    {
        $res = array();
        if(empty($this->jminute))
        {
            $res[] = '*';
        }
        else
        {
            $res[] = $this->jminute;
        }
        if(empty($this->jhour))
        {
            $res[] = '*';
        }
        else
        {
            $res[] = $this->jhour;
        }
        if(empty($this->jdayofmonth))
        {
            $res[] = '*';
        }
        else
        {
            $res[] = $this->jdayofmonth;
        }
        if(empty($this->jmonth))
        {
            $res[] = '*';
        }
        else
        {
            $res[] = $this->jmonth;
        }
        if(empty($this->jdayofweek))
        {
            $res[] = '*';
        }
        else
        {
            $res[] = $this->jdayofweek;
        }

        $result = implode(' ',$res);
        return $result;
    }

    public function getNextSched($currentTime = 'now')
    {
        if ($currentTime == 'now') {
            $currentDate = new \DateTime('now');
        }
        $currentMin = $currentDate->format('i');

    }

    public function  getJcommand()
    {
        return $this->jcommand;
    }

    public function getJparams()
    {
        if(!empty($this->jparams))
        {
            return unserialize($this->jparams);
        }
        else
        {
            return array();
        }
    }

    public function isDue($currentTime = 'now')
    {
        if ($currentTime === 'now') {
            $currentDate = date('Y-m-d H:i');
            $currentTime = strtotime($currentDate);
        }
    }


    public function setJcommand($job)
    {
        $this->jcommand = trim($job);
        return $this;
    }

    public function setJparams($params)
    {
        $this->jparams = serialize($params);
        return $this;
    }

    public function setJcomment($comment)
    {
        $this->jcomment = $comment;
        return $this;

    }

    public function setEnabled($b)
    {
        $this->isenabled = $b;
        return $this;
    }


}