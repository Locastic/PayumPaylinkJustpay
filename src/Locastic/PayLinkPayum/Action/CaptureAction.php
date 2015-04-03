<?php
namespace Locastic\PayLinkPayum\Action;

use PayLink\Customer;
use PayLink\PayLink;
use PayLink\Transaction;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;

class CaptureAction extends PaymentAwareAction implements ApiAwareInterface
{
    /**
     * @var string
     */
    private $templateName;

    /**
     * @param string $templateName
     */
    public function __construct($templateName)
    {
        $this->templateName = $templateName;
    }

    /**
     * @var PayLink
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof PayLink) {
            throw new UnsupportedApiException('Expected instance of PayLink object.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $rawModel = (array) $model;
        if (isset($rawModel['transaction']['token'])) {
            $this->payment->execute($httpRequest = new GetHttpRequest());
            if (isset($httpRequest->query['token'])) {
                $model['token'] = $httpRequest->query['token'];

                $model->replace($this->api->getStatus($httpRequest->query['token'])->getData());

                return;
            }

            $this->payment->execute($renderTemplate = new RenderTemplate($this->templateName, array(
                'returnUrl' => $model['RETURN_URL'],
                'token' => $rawModel['transaction']['token']
            )));

            throw new HttpResponse($renderTemplate->getResult());
        }

        $customer = new Customer();
        $customer->setFirstName($model['NAME.GIVEN']);
        $customer->setLastName($model['NAME.FAMILY']);
        $customer->setStreetAddress($model['ADDRESS.STREET']);
        $customer->setCity($model['ADDRESS.CITY']);
        $customer->setEmail($model['CONTACT.EMAIL']);

        $transaction = new Transaction();
        $transaction->setAmount($model['PRESENTATION.AMOUNT']);
        $transaction->setCurrency($model['PRESENTATION.CURRENCY']);
        $transaction->setDescription($model['PRESENTATION.USAGE']);
        $transaction->setCustomer($customer);

        $model->replace($this->api->generateToken($transaction)->getData());
        $rawModel = (array) $model;

        if (false == $model['RETURN_URL'] && $request->getToken()) {
            $model['RETURN_URL'] = $request->getToken()->getTargetUrl();
        }

        if (false == isset($rawModel['transaction']['token'])) {
            throw new LogicException('Token is required to finish the payment');
        }
        if (false == $model['RETURN_URL']) {
            throw new LogicException('RETURN_URL is missing. Pass RETURN_URL explicitly or pass SecuredCaptureRequest');
        }

        $this->payment->execute($renderTemplate = new RenderTemplate($this->templateName, array(
            'returnUrl' => $model['RETURN_URL'],
            'token' => $rawModel['transaction']['token']
        )));

        throw new HttpResponse($renderTemplate->getResult());
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
