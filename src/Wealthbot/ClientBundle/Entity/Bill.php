<?php

namespace Wealthbot\ClientBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Wealthbot\ClientBundle\Model\Bill as BaseBill;
use Wealthbot\UserBundle\Entity\User;

/**
 * Bill
 */
class Bill extends BaseBill
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $quarter;

    /**
     * @var integer
     */
    protected $year;

    /**
     * @var BillItem|ArrayCollection
     */
    protected $billItems;

    /**
     * @var User
     */
    protected $client;

    /**
     * @var \DateTime
     */
    protected $created_at;

    /**
     * @var \DateTime
     */
    protected $approved_at;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->billItems = new ArrayCollection();

        parent::__construct();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set quarter
     *
     * @param integer $quarter
     * @return $this
     */
    public function setQuarter($quarter)
    {
        $this->quarter = $quarter;

        return $this;
    }

    /**
     * Get quarter
     *
     * @return integer
     */
    public function getQuarter()
    {
        return $this->quarter;
    }

    /**
     * Set year
     *
     * @param integer $year
     * @return $this
     */
    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year
     *
     * @return integer
     */
    public function getYear()
    {
        return $this->year;
    }

    public function addBillItem(BillItem $billItem)
    {
        $this->billItems[] = $billItem;
        return $this;
    }

    public function removeBillItem(BillItem $billItem)
    {
        $this->billItems->removeElement($billItem);
        return $this;
    }

    /**
     * @return ArrayCollection|BillItem[]
     */
    public function getBillItems()
    {
        return $this->billItems;
    }

    public function setBillItems($billItems)
    {
        $this->billItems = $billItems;
        return $this;
    }

    /**
     * @param \Wealthbot\UserBundle\Entity\User $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return \Wealthbot\UserBundle\Entity\User
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param \DateTime $created_at
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $created_at;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }
    /**
     * @var \DateTime
     */
    private $approvedAt;


    /**
     * Set approvedAt
     *
     * @param \DateTime $approvedAt
     * @return Bill
     */
    public function setApprovedAt($approvedAt)
    {
        $this->approved_at = $approved_at;

        return $this;
    }

    /**
     * Get approvedAt
     *
     * @return \DateTime
     */
    public function getApprovedAt()
    {
        return $this->approved_at;
    }
}
