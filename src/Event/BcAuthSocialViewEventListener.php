<?php
declare(strict_types=1);

namespace BcAuthSocial\Event;

use BaserCore\Event\BcViewEventListener;
use Cake\Core\Plugin;
use Cake\Routing\Router;

class BcAuthSocialViewEventListener extends BcViewEventListener
{
    public $events = [
        'BaserCore.Users.beforeGetTemplateFileName' => ['priority' => 10],
    ];

    public function baserCoreUsersBeforeGetTemplateFileName($event): void
    {
        if (!$this->isAction('Users.Login')) {
            return;
        }
        if (Plugin::isLoaded('BcAuthPasskey')) {
            return;
        }

        $request = Router::getRequest();
        $prefix = (string)$request->getParam('prefix');

        if ($prefix === 'Admin') {
            $event->setData('name', 'BcAuthSocial./plugin/BcAdminThird/Admin/Users/login');
            return;
        }

        $event->setData('name', 'BcAuthSocial./plugin/BcFront/Users/login');
    }
}
