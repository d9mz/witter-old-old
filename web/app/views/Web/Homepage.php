<?php
namespace Witter\Views;

class Homepage extends View {
    public function Banned() {
        $userModel = new \Witter\Models\User();

        if(!$userModel->isBanned()) {
            header("Location: /");
        }

        $uid = $userModel->GetUID($_SESSION['Handle']);
        $user = $userModel->GetUser($_SESSION['Handle']);
        $ban = $userModel->GetBan($uid);

        echo $this->Twig->render('admin/banned.twig', array(
            "PageSettings" => $this->PageSettings(),
            "User" => $user,
            "Ban" => $ban,
            "isAppealable" => $userModel->isAppealable($uid),
        ));
    }

    public function View() {
        echo $this->Twig->render('homepage.twig', array(
            "PageSettings" => $this->PageSettings(),
        ));
    }

    public function NotImplemented() {
        echo $this->Twig->render('misc/notimplemented.twig', array(
            "PageSettings" => $this->PageSettings(),
        ));
    }

    public function TermsOfService() {
        echo $this->Twig->render('misc/tos.twig', array(
            "PageSettings" => $this->PageSettings(),
        ));
    }

    public function JacksDen() {
        echo $this->Twig->render('misc/jacksden.twig', array(
            "PageSettings" => $this->PageSettings(),
        ));
    }

    public function Redirect() {
        header("Location: /feed");
        die();
    }
}