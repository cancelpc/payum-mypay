<?php

namespace PayumTW\Mypay\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Sync;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use PayumTW\Mypay\Api;
use PayumTW\Mypay\Request\Api\CreateTransaction;

class CaptureAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * {@inheritdoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (isset($details['uid']) === true) {
            $this->gateway->execute(new Sync($details));

            return;
        }

        $token = $request->getToken();

        $targetUrl = $token->getTargetUrl();
        if (empty($details['success_returl']) === true) {
            $details['success_returl'] = $targetUrl;
        }

        if (empty($details['failure_returl']) === true) {
            $details['failure_returl'] = $targetUrl;
        }

        $notifyToken = $this->tokenFactory->createNotifyToken(
            $token->getGatewayName(),
            $token->getDetails()
        );

        $details[Api::NOTIFY_TOKEN_FIELD] = $notifyToken->getHash();

        $this->gateway->execute(new CreateTransaction($details));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
