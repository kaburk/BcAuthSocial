<?php
declare(strict_types=1);

namespace BcAuthSocial\Controller\Admin;

use BaserCore\Controller\Admin\BcAdminAppController;
use BcAuthSocial\Service\BcAuthSocialConfigsServiceInterface;

class BcAuthSocialConfigsController extends BcAdminAppController
{
    public function index(BcAuthSocialConfigsServiceInterface $service)
    {
        if ($this->request->is(['post', 'put'])) {
            $config = $service->update($this->getRequest()->getData());
            if (!$config->getErrors()) {
                $this->BcMessage->setSuccess(__d('baser_core', 'ソーシャル認証設定を保存しました。'));
                return $this->redirect(['action' => 'index']);
            }
            $this->BcMessage->setError(__d('baser_core', '入力エラーです。内容を修正してください。'));
        } else {
            $config = $service->get();
        }

        $this->set($service->getViewVarsForIndex($config));
    }
}
