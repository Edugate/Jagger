<?php
namespace models;
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
 * Partnership Class
 * 
 * not used yet
 *
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Partnership Model
 *
 * This is a sample model to demonstrate how to use the AnnotationDriver
 *
 * @Entity
 * @Table(name="partnership")
 * @author janusz
 */
class Partnership
{
	/**
	 * @Id
	 * @Column(type="integer", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	/**
	 * @Column(type="string", nullable=false)
	 */
	protected $type;

        /**
         * @ManyToOne(targetEntity="Provider")
         */
        protected $provider;

        /**
         * @ManyToOne(targetEntity="Partner")
         */
        protected $partner;

}
