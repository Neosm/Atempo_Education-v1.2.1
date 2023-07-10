<?php

namespace App\Controller\Admin;

use App\Entity\Lecons;
use App\Form\LeconsType;
use App\Repository\LeconsRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;


/**
 * @Route("/admin/lecons", name="admin_lecons_")
 */
class LeconsController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(LeconsRepository $leconsRepository): Response
    {
        $lecons = $leconsRepository->findAll();

        return $this->render('admin/lecons/index.html.twig',[
            'lecons' => $lecons
        ]);
    }

    /**
     * @Route("/ajouter", name="ajouter")
     */
    public function ajouter(Request $request, SluggerInterface $slugger): Response
    {
        $lecon = new Lecons();
        $form = $this->createForm(LeconsType::class, $lecon, ['required' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lecon->addUser($this->getUser());
            $images = $form->get('thumbnails')->getData();
            
            $lecon->setSlug($slugger->slug($form->get('nom')->getData()));
            if ($images) {
                $originalFilename = pathinfo($images->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $images->guessExtension();

                try {
                    $images->move(
                        $this->getParameter('lecons_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $lecon->setThumbnails($newFilename);
            }
            $pdf = $form->get('pdf')->getData();
            if ($pdf) {
                $originalFilename = pathinfo($pdf->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pdf->guessExtension();

                try {
                    $pdf->move(
                        $this->getParameter('lecons_pdfs_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $lecon->setPdf($newFilename);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($lecon);
            $em->flush();

            return $this->redirectToRoute('admin_lecons_home');
        }


        return $this->render('admin/lecons/ajout.html.twig', [
            'form'=>$form->createView(),
        ]);
    }


     /**
     * @Route("/modifier/{id}", name="modifier")
     */
    public function modifierLecon(Request $request, Lecons $lecons, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(LeconsType::class, $lecons, ['required' => false]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $images = $form->get('thumbnails')->getData();

            $lecons->setSlug($slugger->slug($form->get('nom')->getData()));
            if ($images) {
                $originalFilename = pathinfo($images->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $images->guessExtension();

                try {
                    $images->move(
                        $this->getParameter('lecons_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {

                }
                $lecons->setThumbnails($newFilename);
            }
            $pdf = $form->get('pdf')->getData();
            if ($pdf) {
                $originalFilename = pathinfo($pdf->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pdf->guessExtension();

                try {
                    $pdf->move(
                        $this->getParameter('lecons_pdfs_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $lecons->setPdf($newFilename);
            }

            if($lecons->getProgrammesLecons()->getId() == 1) {
                $getUsers = $form->get('users')->getData();
                foreach($getUsers as $user){
                    $lecons->removeUser($user);
                }
            }
            $lecons->addUser($this->getUser());

            $em = $this->getDoctrine()->getManager();
            $em->persist($lecons);
            $em->flush();

            return $this->redirectToRoute('admin_lecons_home');
        }
        return $this->render('admin/lecons/ajout.html.twig', [
            'form' => $form->createView(),
            'lecon' => $lecons,
        ]);
    }

     /**
     * @Route("/supprimer/{id}", name="supprimer")
     */
    public function supprimerProgramme(Lecons $lecon): Response
    {
        
        $em = $this->getDoctrine()->getManager();
        $em->remove($lecon);
        $em->flush();

        return $this->redirectToRoute('admin_lecons_home');

    }
}