<?php
namespace Witter\Views;

use Witter\Models\Level;
use Witter\Models\Type;

class Admin extends View {
    public function View() {
        $adminModel = new \Witter\Models\Admin();
        $adminModel->checkIfAdminIfNotError();

        // check unmoderated css
    }
}