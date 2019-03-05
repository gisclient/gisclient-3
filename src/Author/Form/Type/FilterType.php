<?php

namespace GisClient\Author\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Form to handle conflict managament when approve vta
 *
 * @author Daniel Degasperi <daniel.degasperi@r3-gis.com>
 */
class FilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('saved_filter_name', TextType::class, [
                'constraints' => [
                    new Constraints\Required(),
                    new Constraints\NotBlank()
                ],
            ])
            ->add('mapset_name', TextType::class, [
                'constraints' => [
                    new Constraints\Required(),
                    new Constraints\NotBlank(),
                    new Constraints\Callback([
                        'callback' => function ($value, $context) {
                            $db = \GCApp::getDB();
                            $sql = "SELECT * FROM ".DB_SCHEMA.".mapset WHERE mapset_name=?";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$value]);
                            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
                            if ($data === false) {
                                $context->addViolation('The mapset does not exists!');
                            }
                        }
                    ])
                ],
            ])
            ->add('layer_id', TextType::class, [
                'constraints' => [
                    new Constraints\Required(),
                    new Constraints\NotBlank(),
                    new Constraints\Callback([
                        'callback' => function ($value, $context) {
                            $normData = $context->getRoot()->getNormData();

                            $db = \GCApp::getDB();
                            $sql = "
                                SELECT * FROM ".DB_SCHEMA.".mapset
                                INNER JOIN ".DB_SCHEMA.".mapset_layergroup USING(mapset_name)
                                INNER JOIN ".DB_SCHEMA.".layer USING(layergroup_id)
                                WHERE mapset_name=? AND layer_id=?
                            ";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$normData['mapset_name'], $normData['layer_id']]);
                            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
                            if ($data === false) {
                                $context->addViolation('The layer is not enabled on this mapset!');
                            }
                        }
                    ])
                ],
            ])
            ->add('saved_filter_scope', TextType::class, [
                'constraints' => [
                    new Constraints\Required(),
                    new Constraints\NotBlank()
                ],
            ])
            ->add('saved_filter_data', TextType::class, [
                'allow_extra_fields' => true,
                'constraints' => [
                    new Constraints\Required(),
                    new Constraints\NotBlank()
                ],
            ])
        ;

        $builder
            ->get('layer_id')
            ->addViewTransformer(new CallbackTransformer(
                function ($layerId) {
                    $composedLayerName = null;
                    if ($layerId !== null) {
                        $db = \GCApp::getDB();
                        $sql = "
                            SELECT project_name||'.'||layergroup_name||'.'||layer_name FROM ".DB_SCHEMA.".theme
                            INNER JOIN ".DB_SCHEMA.".layergroup USING(theme_id)
                            INNER JOIN ".DB_SCHEMA.".layer USING(layergroup_id)
                            WHERE layer_id=?
                        ";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$layerId]);
                        $composedLayerName = $stmt->fetchColumn();
                        if ($composedLayerName === false) {
                            throw new TransformationFailedException(sprintf(
                                'No layer_id found for "%s"',
                                $layerId
                            ));
                        }
                    }
                    return $composedLayerName;
                },
                function ($composedLayerName) {
                    if (strpos($composedLayerName, '.') !== false) {
                        list($projectName, $layergroupName, $layerName) = explode('.', $composedLayerName);
                    } else {
                        throw new TransformationFailedException(
                            'The layer_id must provied layergroup and layername separated by dot.'
                        );
                    }

                    $db = \GCApp::getDB();
                    $sql = "
                        SELECT layer_id FROM ".DB_SCHEMA.".theme
                        INNER JOIN ".DB_SCHEMA.".layergroup USING(theme_id)
                        INNER JOIN ".DB_SCHEMA.".layer USING(layergroup_id)
                        WHERE project_name=? AND layergroup_name=? AND layer_name=?
                    ";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$projectName, $layergroupName, $layerName]);
                    $layerId = $stmt->fetchColumn();
                    if ($layerId === false) {
                        throw new TransformationFailedException(sprintf(
                            'No layer_id found for "%s"',
                            $composedLayerName
                        ));
                    }
                    
                    return $layerId;
                }
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('allow_extra_fields', true);
        $resolver->setDefault('csrf_protection', false);
    }
}
