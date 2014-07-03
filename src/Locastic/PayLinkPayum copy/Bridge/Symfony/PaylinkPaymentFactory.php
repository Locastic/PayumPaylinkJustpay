<?php
namespace Locastic\PayLinkPayum\Bridge\Symfony;

use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Payment\AbstractPaymentFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PaylinkPaymentFactory extends AbstractPaymentFactory
{
    /**
     * @param Definition $paymentDefinition
     * @param ContainerBuilder $container
     * @param $contextName
     * @param array $config
     */
    protected function addActions(Definition $paymentDefinition, ContainerBuilder $container, $contextName, array $config)
    {
        $captureAction = new Definition;
        $captureAction->setClass('Locastic\PayLinkPayum\Action\CaptureAction');
        $captureAction->setPublic(false);
        $captureAction->addTag('payum.action', array(
            'factory' => 'paylink'
        ));
        $container->setDefinition('locastic.paylink_payum.action.capture', $captureAction);

        $statusAction = new Definition;
        $statusAction->setClass('Locastic\PayLinkPayum\Action\StatusAction');
        $statusAction->setPublic(false);
        $statusAction->addTag('payum.action', array(
            'factory' => 'paylink'
        ));
        $container->setDefinition('locastic.paylink_payum.action.status', $statusAction);
    }

    /**
     * @param Definition $paymentDefinition
     * @param ContainerBuilder $container
     * @param $contextName
     * @param array $config
     */
    protected function addApis(Definition $paymentDefinition, ContainerBuilder $container, $contextName, array $config)
    {
        $paylink = new Definition;
        $paylink->setClass('PayLink\PayLink');
        $paylink->addArgument($config['channel_id']);
        $paylink->addArgument($config['sender_id']);
        $paylink->addArgument($config['user_login']);
        $paylink->addArgument($config['user_password']);
        $paylink->addArgument($config['sandbox']);
        $container->setDefinition('locastic.paylink_payum.api', $paylink);

        $paymentDefinition->addMethodCall('addApi', array(new Reference('locastic.paylink_payum.api')));
    }

    /**
     * {@inheritDoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        parent::addConfiguration($builder);

        $builder
            ->children()
                ->scalarNode('channel_id')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('sender_id')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('user_login')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('user_password')->isRequired()->cannotBeEmpty()->end()
                ->booleanNode('sandbox')->defaultValue(true)->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'paylink';
    }
}