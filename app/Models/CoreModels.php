<?php

namespace Models;

abstract class CoreModels
{
    protected $id;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }


    /**
     * Ajoute ou modifie en fonction de si un id est présent
     *
     * @return bool
     */
    public function save()
    {
        if (empty($this->id)) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }
}