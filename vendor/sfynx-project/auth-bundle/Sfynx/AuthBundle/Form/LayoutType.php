<?php
/**
 * This file is part of the <Auth> project.
 *
 * @subpackage   Auth
 * @package    Form
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @since 2012-01-07
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sfynx\AuthBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Description of the LayoutType form.
 *
 * @subpackage   Auth
 * @package    Form
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
class LayoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enabled', 'checkbox', array(
                'data'  => true,
                'label'    => 'pi.form.label.field.enabled',
            ))
            ->add('name', 'text', array(
                'label' => "pi.form.label.field.name"
             ))
            ->add('filePc')
            ->add('fileMobile')
            ->add('configXml')
        ;
    }

    public function getName()
    {
        return 'piapp_adminbundle_layouttype';
    }
}
