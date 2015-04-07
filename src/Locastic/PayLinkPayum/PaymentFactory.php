<?php
namespace Locastic\PayLinkPayum;

use Locastic\PayLinkPayum\Action\CaptureAction;
use Locastic\PayLinkPayum\Action\StatusAction;
use PayLink\PayLink;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\PaymentFactoryInterface;
use Payum\Core\PaymentFactory as CorePaymentFactory;

class PaymentFactory implements PaymentFactoryInterface
{
    /**
     * @var PaymentFactoryInterface
     */
    protected $corePaymentFactory;

    /**
     * @var array
     */
    private $defaultConfig;

    /**
     * @param array $defaultConfig
     * @param PaymentFactoryInterface $corePaymentFactory
     */
    public function __construct(array $defaultConfig = array(), PaymentFactoryInterface $corePaymentFactory = null)
    {
        $this->corePaymentFactory = $corePaymentFactory ?: new CorePaymentFactory();
        $this->defaultConfig = $defaultConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $config = array())
    {
        return $this->corePaymentFactory->create($this->createConfig($config));
    }

    /**
     * {@inheritDoc}
     */
    public function createConfig(array $config = array())
    {
        $config = ArrayObject::ensureArrayObject($config);
        $config->defaults($this->defaultConfig);
        $config->defaults($this->corePaymentFactory->createConfig((array) $config));

        $config->defaults(array(
            'payum.factory_name' => 'paylink',
            'payum.factory_title' => 'PayLink',
            'payum.action.capture' => function (ArrayObject $config) {
                return new CaptureAction($config['payum.template.widget']);
            },
            'payum.action.status' => new StatusAction(),

            'payum.template.widget' => '@LocasticPaylink/widget.html.twig',
        ));

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'channel_id' => '',
                'sender_id' => '',
                'user_login' => '',
                'user_password' => '',
                'sandbox' => true,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = array(
                'channel_id',
                'sender_id',
                'user_login',
                'user_password',
            );

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $api = new PayLink(
                    $config['channel_id'],
                    $config['sender_id'],
                    $config['user_login'],
                    $config['user_password'],
                    $config['sandbox']
                );

                return $api;
            };
        }

        return (array) $config;
    }
}