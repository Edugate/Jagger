<?php
/**
 * Created by PhpStorm.
 * User: janul
 * Date: 20/05/15
 * Time: 17:11
 */

namespace models;

use Doctrine\ORM\Mapping\Version;

/**
 * @Entity(readOnly=true)
 * @Table(name="metadatarevision")
 * @HasLifecycleCallbacks
 * @author janusz
 */
class MetadataRevision
{

    /**
     * @Id
     * @Column(name="id",type="bigint", unique=true)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="metadatadumps")
     * @JoinColumn(name="provider_id", referencedColumnName="id")
     */
    protected $provider;

    /**
     * @Column(type="text")
     */
    protected $metadata;


    /**
     * @Column(type="datetime")
     */
    protected $generated;


    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }

    public function setMeta($metadata)
    {
        $this->metadata = base64_encode($metadata);
        return $this;
    }

    /**
     * @prePersist
     */
    public function created()
    {
        $this->generated = new \DateTime("now", new \DateTimeZone('UTC'));
    }

}
