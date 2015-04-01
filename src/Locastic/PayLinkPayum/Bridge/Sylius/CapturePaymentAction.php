<?php
namespace Locastic\PayLinkPayum\Bridge\Sylius;

use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;

class CapturePaymentAction extends PaymentAwareAction
{
    /**
     * @var string
     */
    protected $templateName;

    /**
     * @param string $templateName
     */
    public function __construct($templateName)
    {
        $this->templateName = $templateName;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var $payment PaymentInterface */
        $payment = $request->getModel();

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $details = $payment->getDetails();
        if (empty($details)) {
            $details = array();
            $details['NAME.GIVEN'] = $order->getBillingAddress()->getFirstName() ?: 'Unknown';
            $details['NAME.FAMILY'] = $order->getBillingAddress()->getLastName() ?: 'Unknown';
            $details['CONTACT.EMAIL'] = $order->getUser()->getEmail();
            $details['ADDRESS.STREET'] = $order->getBillingAddress()->getStreet();
            $details['ADDRESS.CITY'] = $order->getBillingAddress()->getCity();
            $details['PRESENTATION.AMOUNT'] = $order->getTotal() / 100;
            $details['PRESENTATION.CURRENCY'] = $order->getCurrency();
            $details['PRESENTATION.USAGE'] = sprintf('Order containing %d items for a total of %01.2f', $order->getItems()->count(), $order->getTotal() / 100);

            $payment->setDetails($details);
        }

        $details = ArrayObject::ensureArrayObject($details);

        try {
            $request->setModel($details);
            $this->payment->execute($request);

            $payment->setDetails((array) $details);
        } catch (\Exception $e) {
            $payment->setDetails((array) $details);

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof PaymentInterface
        ;
    }
}
