<?php
namespace Locastic\PayLinkPayum\Bridge\Sylius;

use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\CaptureRequest;
use Payum\Core\Request\ResponseInteractiveRequest;
use Payum\Core\Request\SimpleStatusRequest;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\Templating\EngineInterface;

class CapturePaymentAction extends PaymentAwareAction
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @param EngineInterface $templating
     * @param string $templateName
     */
    public function __construct(EngineInterface $templating, $templateName)
    {
        $this->templating = $templating;
        $this->templateName = $templateName;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request CaptureRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

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
            $request->setModel($payment);
        } catch (ResponseInteractiveRequest $interactiveRequest) {
            $payment->setDetails((array) $details);
            $request->setModel($payment);
            $rawDetails = (array) $details;

            throw new ResponseInteractiveRequest($this->templating->render($this->templateName, array(
                'return_url' => $details['RETURN_URL'],
                'token' => $rawDetails['transaction']['token'],
            )));
        } catch (\Exception $e) {
            $payment->setDetails((array) $details);
            $request->setModel($payment);

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CaptureRequest &&
            $request->getModel() instanceof PaymentInterface
        ;
    }
}
