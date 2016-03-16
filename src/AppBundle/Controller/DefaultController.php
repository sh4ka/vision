<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Gaufrette\Adapter\GoogleCloudStorage;
use Knp\Bundle\GaufretteBundle\KnpGaufretteBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\File;

use Gaufrette\Filesystem;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $product = new Product();
        $fs = $this->container->get('knp_gaufrette.filesystem_map')->get('product');
        $client = new \Google_Client();
        $client->setClientId('300510150198-dethfusmiaijh3k4dagmp774tvkcpnsk.apps.googleusercontent.com');
        $client->setApplicationName('Gaufrette');
        $cred = new \Google_Auth_AssertionCredentials(
            'applicadito@vision-test-1250.iam.gserviceaccount.com',
            array(\Google_Service_Storage::DEVSTORAGE_FULL_CONTROL),
            file_get_contents($this->get('kernel')->getRootDir().'/../vision-test-ca6b016590c0.p12')
        );
        $client->setAssertionCredentials($cred);
        if ($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion($cred);
        }

        $service = new \Google_Service_Storage($client);
        $adapter = new GoogleCloudStorage($service, 'vision-symfony-test054321', array(
            'acl' => 'public',
        ), true);
        $filesystem = new Filesystem($adapter);

        $form = $this->createFormBuilder($product)
            ->add('imageFile', FileType::class)
            ->add('save', SubmitType::class, array('label' => 'upload'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var Product $product
             */
            $product = $form->getData();

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($product);
            $em->flush();
            // upload to gcs
            $file = $product->getImageFile();
            if(!$filesystem->has(sha1_file($file))){
                $googleFilename = sha1_file($file).'.'.$file->getExtension();
                $filesystem->write($googleFilename, file_get_contents($file->getRealPath()));
            }
        }

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
