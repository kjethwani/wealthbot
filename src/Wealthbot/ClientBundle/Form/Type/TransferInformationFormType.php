<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amalyuhin
 * Date: 13.02.13
 * Time: 12:59
 * To change this template use File | Settings | File Templates.
 */

namespace Wealthbot\ClientBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Wealthbot\ClientBundle\Entity\TransferCustodianQuestionAnswer;
use Wealthbot\ClientBundle\Entity\TransferInformation;
use Wealthbot\SignatureBundle\Manager\AccountDocusignManager;

class TransferInformationFormType extends AbstractType
{
    private $adm;
    private $isPreSaved;

    public function __construct(AccountDocusignManager $adm, $isPreSaved = false)
    {
        $this->adm = $adm;
        $this->isPreSaved = $isPreSaved;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title_first', 'text', [
                'constraints' => [new NotBlank()],
            ])
            ->add('title_middle', 'text', [
                'constraints' => [new NotBlank()],
            ])
            ->add('title_last', 'text', [
                'constraints' => [new NotBlank()],
            ])
            ->add('transfer_from', 'choice', [
                'choices' => TransferInformation::getTransferFromChoices(),
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('account_number', 'text', ['required' => true])
            ->add('firm_address', 'text', ['required' => true])
            ->add('phone_number', 'text', ['required' => true])
            ->add('is_include_policy', 'choice', [
                'choices' => [true => 'Yes', false => 'No'],
                'expanded' => true,
                'label' => ' ',
                'constraints' => [new NotBlank()],
            ])
            ->add('transfer_shares_cash', 'choice', [
                'choices' => [1 => 'Transfer my shares in-kind OR', 0 => 'sell my shares, and then transfer cash'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('insurance_policy_type', 'choice', [
                'choices' => TransferInformation::getInsurancePolicyTypeChoices(),
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('penalty_amount', 'number', ['required' => false])
            ->add('redeem_certificates_deposit', 'radio', ['required' => false])
            ->add('statementDocument', new PdfDocumentFormType());

        $factory = $builder->getFormFactory();
        $adm = $this->adm;

        $updateFields = function (FormInterface $form, TransferInformation $data) use ($factory, $adm) {
            $account = $data->getClientAccount();
            if ($account) {
                if (!$adm->isUsedDocusign($account->getId())) {
                    $form->add($factory->createNamed('delivering_account_title', 'text', null, [
                            'required' => false,
                            'auto_initialize' => false,
                        ]))
                        ->add($factory->createNamed('ameritrade_account_title', 'text', null, [
                            'required' => false,
                            'auto_initialize' => false,
                        ]));
                }

                $form->add($factory->createNamed('financial_institution', 'text', null, [
                    'required' => true,
                    'read_only' => true,
                    'data' => $account->getFinancialInstitution(),
                    'auto_initialize' => false,
                ]));

                if ($account->isJointType()) {
                    $form->add($factory->createNamed('joint_title_first', 'text', null, [
                            'constraints' => [new NotBlank()],
                            'auto_initialize' => false,
                        ]))
                        ->add($factory->createNamed('joint_title_middle', 'text', null, [
                            'constraints' => [new NotBlank()],
                            'auto_initialize' => false,
                        ]))
                        ->add($factory->createNamed('joint_title_last', 'text', null, [
                            'constraints' => [new NotBlank()],
                            'auto_initialize' => false,
                        ]));
                }
            }

            if ($data->getTransferCustodian()) {
                $answers = $data->getQuestionnaireAnswers();
                if ($answers->isEmpty()) {
                    $questions = [$data->getTransferCustodian()->getTransferCustodianQuestion()];

                    $answers = new ArrayCollection();
                    foreach ($questions as $question) {
                        if (null !== $question) {
                            $answer = new TransferCustodianQuestionAnswer();
                            $answer->setQuestion($question);
                            $answer->setTransferInformation($data);

                            $answers->add($answer);
                        }
                    }
                }

                if ($answers->count()) {
                    $form->add($factory->createNamed('questionnaireAnswers', 'collection', $answers, [
                        'type' => new TransferInformationQuestionAnswerFormType(),
                        'label' => ' ',
                        'auto_initialize' => false,
                    ]));
                }
            }
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($updateFields) {
            /** @var TransferInformation $data */
            $data = $event->getData();
            $form = $event->getForm();

            if (null === $data) {
                return;
            }

            $updateFields($form, $data);
        });

        if (!$this->isPreSaved) {
            $builder->addEventListener(FormEvents::SUBMIT, [$this, 'validate']);
        }
    }

    public function validate(FormEvent $event)
    {
        /** @var $data TransferInformation */
        $data = $event->getData();
        $form = $event->getForm();

        $this->validateTextFields($form, $data);
        $this->validatePhone($form, $data);
        $this->validateTransferFrom($form, $data);
        $this->validateInsurancePolicyType($form, $data);
        $this->validateStatementDocument($form, $data);
    }

    protected function validateInsurancePolicyType(FormInterface $form, TransferInformation $data)
    {
        $insurancePolicyType = $data->getInsurancePolicyType();
        $penaltyAmount = $data->getPenaltyAmount();

        if (($insurancePolicyType === TransferInformation::INSURANCE_POLICY_TYPE_TERMINATE_CONTACT_POLICY) ||
            ($insurancePolicyType === TransferInformation::INSURANCE_POLICY_TYPE_TRANSFER_PENALTY_FREE)
        ) {
            $data->setPenaltyAmount(null);
        } elseif ($insurancePolicyType === TransferInformation::INSURANCE_POLICY_TYPE_TRANSFER_PENALTY_FREE_AMOUNT) {
            if (null === $penaltyAmount || filter_var($penaltyAmount, FILTER_VALIDATE_FLOAT) === false) {
                $form->get('penalty_amount')->addError(new FormError('Enter valid penalty amount.'));
            }
        }
    }

    protected function validateTextFields(FormInterface $form, TransferInformation $data)
    {
        $title = $data->getAccountTitle();
        $number = $data->getAccountNumber();
        $financialInstitution = $data->getFinancialInstitution();
        $firmAddress = $data->getFirmAddress();

        if ($form->has('account_title') && (is_null($title) || !is_string($title))) {
            $form->get('account_title')->addError(new FormError('Required.'));
        }
        if ($form->has('account_number') && (is_null($number) || !is_string($number))) {
            $form->get('account_number')->addError(new FormError('Required.'));
        }
        if ($form->has('financial_institution') && (is_null($financialInstitution) || !is_string($financialInstitution))) {
            $form->get('financial_institution')->addError(new FormError('Required.'));
        }
        if ($form->has('firm_address') && (is_null($firmAddress) || !is_string($firmAddress))) {
            $form->get('firm_address')->addError(new FormError('Required.'));
        }
    }

    protected function validatePhone(FormInterface $form, TransferInformation $data)
    {
        $phoneDigits = 10;
        $phoneNum = str_replace([' ', '-', '(', ')'], '', $data->getPhoneNumber());

        if (!is_numeric($phoneNum)) {
            $form->get('phone_number')->addError(new FormError('Enter correct phone number.'));
        } elseif (strlen($phoneNum) !== $phoneDigits) {
            $form->get('phone_number')->addError(new FormError("Phone number must be {$phoneDigits} digits."));
        } else {
            $data->setPhoneNumber($phoneNum);
        }
    }

    protected function validateTransferFrom(FormInterface $form, TransferInformation $data)
    {
        $transferFrom = $data->getTransferFrom();

        if (null === $transferFrom) {
            $form->get('transfer_from')->addError(new FormError('Required.'));
        } else {
            switch ($transferFrom) {
                case TransferInformation::TRANSFER_FROM_BROKERAGE_FIRM:
                    $data->setTransferSharesCash(null);
                    $data->setInsurancePolicyType(null);
                    $data->setPenaltyAmount(null);
                    $data->setRedeemCertificatesDeposit(null);

                    break;
                case TransferInformation::TRANSFER_FROM_MUTUAL_FUND_COMPANY:
                    if (null === $data->getTransferSharesCash()) {
                        $form->get('transfer_shares_cash')->addError(new FormError('Required.'));
                    }

                    $data->setInsurancePolicyType(null);
                    $data->setPenaltyAmount(null);
                    $data->setRedeemCertificatesDeposit(null);

                    break;
                case TransferInformation::TRANSFER_FROM_BANK:
                    if (null === $data->getInsurancePolicyType()) {
                        $form->get('insurance_policy_type')->addError(new FormError('Choose an option.'));
                    }

                    $data->setTransferSharesCash(null);
                    $data->setRedeemCertificatesDeposit(null);

                    break;
                case TransferInformation::TRANSFER_FROM_DEPOSIT_CERTIFICATES:
                    if (null === $data->getRedeemCertificatesDeposit() || !$data->getRedeemCertificatesDeposit()) {
                        $form->get('redeem_certificates_deposit')->addError(new FormError('Required.'));
                    }

                    $data->setTransferSharesCash(null);
                    $data->setInsurancePolicyType(null);
                    $data->setPenaltyAmount(null);

                    break;
            }
        }
    }

    protected function validateStatementDocument(FormInterface $form, TransferInformation $data)
    {
        if (!$data->getStatementDocument()) {
            $form->get('statementDocument')->addError(new FormError('Upload a file.'));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Wealthbot\ClientBundle\Entity\TransferInformation',
            'cascade_validation' => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'transfer_information';
    }
}
