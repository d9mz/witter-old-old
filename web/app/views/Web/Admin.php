<?php
namespace Witter\Views;

use Witter\Models\Level;
use Witter\Models\Type;
use Witter\Models\ModeratableTypes;

/* [general guide (js)]
    moderate user button?
    ------------------------
    add `.admin-moderate-item` to any button/link
    will pop up a new "moderate" popup that will allow moderation
    of anybody
    -----------------------
    select reason, and time til' unban etc.
*/

class Admin extends View {
    public function View() {
        $adminModel = new \Witter\Models\Admin();
        $adminModel->checkIfAdminIfNotError();

        // check unmoderated css first
        $users = $adminModel->getUnmoderatedItems(ModeratableTypes::CSS);

        echo $this->Twig->render('admin/admin.twig', array(
            "PageSettings" => $this->PageSettings("admin", "for all of your moderating needs"),
            "Users" => $users,
        ));
    }
}