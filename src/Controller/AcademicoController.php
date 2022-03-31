<?php

namespace App\Controller;

use App\Entity\Academico;
use App\Form\VotoType;
use App\Repository\AcademicoRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


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
                    'Correo no encontrado, favor de comunicarse con el Coordinador del Posgrado'
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
                // https://github.com/symfony/symfony/issues/35990

                $transport = new EsmtpTransport('churipo.matmor.unam.mx', 465, false);
                $mailer = new Mailer($transport);

                $urlVoto = "https://dev.matmor.unam.mx/consulta-nucleo-academico/voto/" . $academico->getToken();

                $email = (new TemplatedEmail())
                    ->from('info@matmor.unam.mx')
                    ->to($academico->getEmail())
                    ->subject('Consulta Núcleo Académico del Posgrado')
                    ->text('Consulta Núcleo Académico del Posgrado
                    
Favor de votar en el siguiente enlace

'. $urlVoto);
//                    ->html('<p>Votación</p>
//                                      <a href="' . $urlVoto . '">'. $urlVoto . '</a>
//
//                        ');
//                    ->htmlTemplate('emails/votoemail.html.twig')
//                    ->textTemplate('emails/votoemail.txt.twig')
//                    ->context([
//                        'urlVoto' => $urlVoto,
//                    ]);

                $mailer->send($email);
//                try {
//                    $mailer->send($email);
//                } catch (TransportExceptionInterface $e) {
//                    // some error prevented the email sending; display an
//                    // error message or try to resend the message
//                }

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
    public function voto(Request $request, ManagerRegistry $doctrine, Academico $academico, AcademicoRepository $academicoRepository): Response
    {
        $entityManager = $doctrine->getManager();

        $form = $this->createForm(VotoType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $academico->setVoto($data["voto"]);

            $dateNow = new \DateTime("now");
            $academico->setFecha($dateNow);

            $entityManager->persist($academico);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Voto recibido!'
            );
        }

        $votosSi = $academicoRepository
            ->cuentaVotos(1);

        $votosNo = $academicoRepository
            ->cuentaVotos(0);

        return $this->renderForm('consulta/voto.html.twig', [
            'form' => $form,
            'academico' => $academico,
            'votosSi' => $votosSi,
            'votosNo' => $votosNo,
        ]);
    }


}
