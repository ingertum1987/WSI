<?php

namespace App\Controller\Cabinet\User\Profile;

use App\Entity\User;
use App\Model\UploadInterface;
use Imagine\Gd\Imagine;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FotoController extends Controller
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @Route("/", name="cabinet_user_profile_foto_index")
     * @Method("POST")
     * @param Request $request
     * @param Imagine $imagine
     * @return JsonResponse
     */
    public function index(Request $request, Imagine $imagine): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $slim = $request->request->get('slim');
        $data = json_decode($slim);
        $field = $data->output->field;
        $filename = $user->getFotoFilename();
        $path = UploadInterface::UPLOAD_DIR . User::FOTO_DIR;
        $filePath = $path . UploadInterface::IMAGE_SUBDIR . $user->getFotoSubdir();
        $file = $request->files->get($field);
        try {
            if (!is_dir($filePath)) {
                $this->filesystem->mkdir($filePath, UploadInterface::DIR_CHMOD);
            }
            $file->move($filePath, $filename);

            $user->setFoto(true);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();

            return new JsonResponse([
                'result' => true,
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(false, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/remove", name="cabinet_user_profile_foto_remove")
     * @param Filesystem $filesystem
     * @return JsonResponse
     */
    public function remove(Filesystem $filesystem): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        try {
            $user->removeFilesAndDirs($filesystem);
            $user->setFoto(false);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();

            return new JsonResponse(true);
        } catch (\Exception $e) {
            return new JsonResponse(false, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}