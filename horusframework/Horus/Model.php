<?php
namespace Horus;

new \Horus\Database();

class Model extends \Illuminate\Database\Eloquent\Model {
	public function __construct() {
        parent::__construct();
    }
}
