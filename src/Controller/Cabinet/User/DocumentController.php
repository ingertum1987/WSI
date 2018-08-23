<?php

namespace App\Controller\Cabinet\User;

use App\Entity\Document;
use App\Form\Cabinet\User\DocumentType;
use App\Service\File;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    /**
     * @Route("/", name="cabinet_user_document_index")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $documentRepository = $this->getDoctrine()->getRepository('App:Document');
        $documents = $documentRepository->findAll();
        if (null !== $request->request->get('removeDoc')) {
            $removeDocument = $documentRepository->find($request->request->get('id'));
            $filesystem = new Filesystem();
            $targetDir = $this->getParameter('docs_directory');
            $filesystem->remove($targetDir . '/' . $removeDocument->getImg());
            $filesystem->remove($targetDir . '/' . $removeDocument->getFile());
            $em = $this->getDoctrine()->getManager();
            $em->remove($removeDocument);
            $em->flush();

            return $this->redirectToRoute('cabinet_user_document_index');
        }
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document, ['attr' => ['class' => 'form-horizontal']]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $img */
            $img = $document->getImg();
            $fileName = $this->get(File::class)->generateUniqueFileName() . '.' . $img->guessExtension();
            $img->move(
                $this->getParameter('docs_directory'),
                $fileName
            );
            $document->setImg($fileName);
            /** @var UploadedFile $file */
            $file = $document->getFile();
            $fileName = $this->get(File::class)->generateUniqueFileName() . '.' . $file->guessExtension();
            $file->move(
                $this->getParameter('docs_directory'),
                $fileName
            );
            $document->setFile($fileName);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($document);
            $entityManager->flush();

            return $this->redirect($this->generateUrl('cabinet_user_document_index'));
        }

        return $this->render('cabinet/user/document/index.html.twig', [
            'documents' => $documents,
            'form' => $form->createView(),
        ]);
    }
}