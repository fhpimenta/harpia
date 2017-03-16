<?php

namespace Modulos\Academico\Events;

use Harpia\Event\Event;
use Modulos\Core\Model\BaseModel;

class DeleteGrupoEvent extends Event
{
    public function __construct(BaseModel $entry, $action = "DELETE")
    {
        parent::__construct($entry, $action);
    }
}
