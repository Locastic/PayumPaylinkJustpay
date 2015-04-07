<?php
namespace Locastic\PayLinkPayum\Bridge\Symfony;

use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Payment\AbstractPaymentFactory;
use Payum\Core\Bridge\Twig\TwigFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class PayLinkPaymentFactory extends AbstractPaymentFactory implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'paylink';
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
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('twig', array(
            'paths' => array_flip(array_filter(array(
                'PayumCore' => TwigFactory::guessViewsPath('Payum\Core\Payment'),
                'LocasticPayLink' => TwigFactory::guessViewsPath('Locastic\PayLinkPayum\PaymentFactory'),
            )))
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        parent::load($container);

        $container->setParameter('locastic.paylink_payum.template.widget', '@LocasticPayLink/widget.html.twig');
    }

    /**
     * {@inheritDoc}
     */
    protected function createFactoryConfig()
    {
        $config = parent::createFactoryConfig();
        $config['payum.template.widget'] = new Parameter('locastic.paylink_payum.template.widget');

        return $config;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPayumPaymentFactoryClass()
    {
        return 'Locastic\PayLinkPayum\PaymentFactory';
    }

    /**
     * {@inheritDoc}
     */
    protected function getComposerPackage()
    {
        return 'locastic/paylink-payum';
    }
}
