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
     * @Column(type="string", length=512, nullable = false)
     */
    protected $jparams;

	/**
	 * @Column(type="string",length=512, nullable = true)
	 */
	protected $jservers;

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


    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $istemplate;


    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $lastrun;


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

    public function getTemplate()
    {
        return $this->istemplate;
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

	public function getJservers()
	{
		if(!empty($this->jservers))
		{
			return unserialize($this->jservers);
		}
		return null;
	}


    public function getToNotify()
    {
        return $this->tonotify;
    }

    public function getJcomment()
    {
        return $this->jcomment;
    }

    public function getLastRun()
    {
        return $this->lastrun;
    }

    public function isLastRunMatchRange(\DateTime $arg1, $range = 60 )
    {
        if(empty($this->lastrun))
        {
            return false;
        }
        $lastrunU = $this->lastrun->format('U');
        $arg1U = $arg1->format('U');
        if(abs($lastrunU-$arg1U) <= $range)
        {
            return true;
        }
        return false;


    }

    public function setJcommand($job)
    {
        $this->jcommand = trim($job);
        return $this;
    }

    public function setMinutes($a=null)
    {
        $this->jminute = $a;
        return $this;
    }
    public function setHours($a=null)
    {
        $this->jhour = $a;
        return $this;
    }
    public function setDayofweek($a=null)
    {
        $this->jdayofweek = $a;
        return $this;
    }
    public function setMonths($a=null)
    {
        $this->jmonth = $a;
        return $this;
    }
    public function setDayofmonth($a=null)
    {
        $this->jdayofmonth = $a;
        return $this;
    }

    public function setJparams($params)
    {
        $this->jparams = serialize($params);
        return $this;
    }
	public function addJserver($ar)
	{
		if(empty($ar) || !is_array($ar) || !isset($ar['ip']) || !isset($ar['port']))
		{
			\log_message('error',__METHOD__.' invalid param provided');
		    return null;
		}
		$current = $this->getJservers();
		$isExist = false;
		if(is_array($current))
		{
			foreach($current as $v)
			{
				if(strcasecmp($v['ip'],$ar['ip']) == 0 && strcasecmp($v['port'],$ar['port']) == 0 )
				{
					$isExist = true;
					break;
				}
			}
			if(!$isExist)
			{
				$current[] = $ar;
				$this->jservers = serialize($current);
			}
		}
		return $this;


	}

    public function setJcomment($comment)
    {
        $this->jcomment = $comment;
        return $this;

    }

    public function setToNotify($a)
    {
        $this->tonotify = (bool) $a;
        return $this;
    }

    public function setEnabled($b)
    {
        $this->isenabled = $b;
        return $this;
    }

    public function setTemplate($b)
    {
        $this->istemplate = $b;
        return $this;
    }

    public function setLastRun()
    {
        $currentTime = new \DateTime("now", new \DateTimeZone('UTC'));
        $this->lastrun = $currentTime;
        return $this;
    }


}