<?php
namespace Locastic\PayLinkPayum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $rawModel = (array) ArrayObject::ensureArrayObject($request->getModel());

        if (false == isset($rawModel['transaction']['processing']['result'])) {
            if (isset($rawModel['transaction']['token'])) {
                $request->markPending();
            } else {
                $request->markNew();
            }

            return;
        }

        if ($rawModel['transaction']['processing']['result'] == 'ACK') {
            $request->markCaptured();

            return;
        }

        if ($rawModel['transaction']['processing']['result'] != 'WAITING FOR SHOPPER') {
            $request->markPending();

            return;
        }

        $request->markUnknown();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}