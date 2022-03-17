<?php

namespace App\Controller;

use App\Entity\Academico;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class AcademicoController extends AbstractController
{
    /**
     * @Route("/", name="app_academico")
     */
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $data = [];
        $name = "";
        $token = "";

        $entityManager = $doctrine->getManager();

        $defaultData = ['message' => 'Correo electrónico'];

        $form = $this->createFormBuilder($defaultData)
            ->add('email', EmailType::class)
            ->add('send', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $academico = $doctrine->getRepository(Academico::class)->findOneBy(['email' => $data['email']]);

            if (!$academico) {
                $this->addFlash(
                    'danger',
                    'Correo no encontrado'
                );

                $form
                    ->get('email')
                    ->addError(new FormError('Correo electrónico no válido'));

            }
            else {

                $name = $academico->getNombre();

                // Si no tiene token se genera
                if(!$academico->getToken()) {
                    $academico->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));

                    $entityManager->persist($academico);
                    $entityManager->flush();
                }

                $token = $academico->getToken();

                // Envía Correo

                $transport = new EsmtpTransport('churipo.matmor.unam.mx', 465, false);
                $mailer = new Mailer($transport);

                $urlVoto = "https://dev.matmor.unam.mx/consulta-pccm/voto/" . $academico->getToken();

                $email = (new Email())
                    ->from('info@matmor.unam.mx')
                    ->to($academico->getEmail())
                    //->cc('cc@example.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)
                    ->subject('Votación Comisión Dictaminadora')
                    ->text('Votación Comisión Dictaminadora
                                     '. $urlVoto)
                    ->html('<p>Votación Comisión Dictaminadora</p>
                                      <a href="' . $urlVoto . '">'. $urlVoto . '</a>
                        ');

                //$mailer->send($email);
                try {
                    $mailer->send($email);
                } catch (TransportExceptionInterface $e) {
                    // some error prevented the email sending; display an
                    // error message or try to resend the message
                }


                $this->addFlash(
                    'success',
                    'Se envió un correo con las instrucciones de voto'
                );
            }
        }

        return $this->renderForm('consulta/index.html.twig', [
            'form' => $form,
            'data' => $data,
            'name' => $name,
            'token' => $token,
        ]);
    }

    /**
     * @Route("/voto/{token}", name="consulta_voto")
     */
    public function voto(Request $request, ManagerRegistry $doctrine, Academico $academico): Response
    {
        $entityManager = $doctrine->getManager();

        $repository = $doctrine->getRepository(Elegible::class);
        $elegibles = $repository->findBy(
            array(), array('nombre'=>'asc')
        );

        $form = $this->createForm(BoletaType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $academico->setVoto($data["category"]);

            $dateNow = new \DateTime("now");
            $academico->setFecha($dateNow);

            $entityManager->persist($academico);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Voto recibido!'
            );
        }

        return $this->renderForm('consulta/voto.html.twig', [
            'form' => $form,
            'elegibles' => $elegibles,
            'academico' => $academico,
        ]);
    }


}
