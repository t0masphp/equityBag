<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User extends BaseUser {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Share", mappedBy="user")
     */
    protected $shares;

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId( $id ) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getShares() {
        return $this->shares;
    }

    /**
     * @param mixed $shares
     */
    public function setShares( $shares ) {
        $this->shares = $shares;
    }

    /**
     * @return int
     */
    public function hasShares() {
        return count( $this->shares );
    }
}