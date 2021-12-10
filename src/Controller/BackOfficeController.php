<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class BackOfficeController extends AbstractController
{
    # Méthode qui affiche la page Home du backoffice 
    #[Route('/admin', name: 'app_admin')]
    public function adminHome(): Response
    {
        return $this->render('back_office/index.html.twig');
    }

    # Méthode qui affiche la page Home du backoffice 
    #[Route('/admin/articles', name: 'app_admin_articles')]
    #[Route('/admin/article/{id}/remove', name: 'app_admin_articles_remove')]
    public function adminArticles(EntityManagerInterface $manager, ArticleRepository $repoArticle, Article $artRemove = null): Response
    {
        // dd($artRemove);
        // 
        $colonnes = $manager->getClassMetadata(Article::class)->getFieldNames();
        // dd($colonnes);

        // SELECT * FORM article + FETCH_ALL
        $articles = $repoArticle->findAll();
        // dd($articles);

        // Traitement suppression article en BDD
        if($artRemove)
        {
            // Avant de supprimer l'article dans la bdd, on stock son ID afin de l'intégrer dans la message de validation de suppression (addFlash)
            $id = $artRemove->getId();

            $manager->remove($artRemove);
            $manager->flush();

            $this->addFlash('success', "l'article n° $id a été supprimé avec succès.");

            return $this->redirectToRoute('app_admin_articles');
        }

        return $this->render('back_office/admin_articles.html.twig', [
            'colonnes' => $colonnes,
            'articles' => $articles
        ]);
    }

    /* 
        Exo: création d'une nouvelle méthode permettant d'insérer et de modifier l'article en BDD

        1. Créer une route '/admin/article/add' (name:app_admin_articles_add)
        2. Créer la méthode adminArticleForm()
        3. créer un nouveau template 'admin_article_form.html.twig'
        4. Importer et créer le formulaire au sein de la méthode adminArticleForm() (createForm)
        5. Afficher le formulaire sur le template 'admin_article_form.html.twig'
        6. Gérer l'upload de la photo
        7. Dans la méthode adminArticleForm(), réaliser le traitement permettant d'insérer un nouvel article en BDD (persist() / flush())

        */

        #[Route('/admin/articles/add', name: 'app_admin_articles_add')]
        #[Route('/admin/articles/{id}/update', name: 'app_admin_articles_update')]
        public function adminArticleForm(Request $request, EntityManagerInterface $manager, ArticleRepository $repoArticle, Article $article = null):Response
        {   
            if($article)
            {
                $photoActuelle = $article->getPhoto();
            }
            if(!$article)
            {
                $article = new Article;

            }

             $formAdminArticle = $this->createForm(ArticleType::class, $article);

                $formAdminArticle->handleRequest($request);

                if($formAdminArticle->isSubmitted() && $formAdminArticle->isValid())
                {
                    if($article->getId())
                         $txt = "modifié";

                    else  
                         $txt = 'enregistré';

                    $article->setDate(new \DateTime);

                      //dd('article');

                      // DEBUT TRAITEMENT PHOTO 
                      $photo = $formAdminArticle->get('photo')->getData();
                      dd($photo);

                      if($photo)
                      {
                        $nomOriginePhoto = pathinfo($photo->getOriginalName(), PATHINFO_FILENAME);
                        dd($nomOriginePhoto);

                        $nouveauNomFichier = $nomOriginePhoto . '-' . uniqid() . '-' . $photo->guessExtension();
                        dd($nouveauNomFichier);


                        try
                        {
                            $photo->move(
                                $this->getParameter('photo_directory'),
                                $nouveauNomFichier
                            );
                        }
                        catch(FileException $e)
                        {
                            
                        }

                        // on enregistre la photo en BDD
                        $article->setPhoto($nouveauNomFichier);
                      }

                      else
                      {
                          if(isset($photoActuelle))
                              $article->setPhoto($photoActuelle);

                          else
                              $article->setPhoto(null);
                      }

                      // FIN TRAITEMENT PHOTO

                      $manager->persist($article);
                      $manager->flush();

                      $this->addFlash('success', "L'article a éte $txt avec succès");


                      return $this->redirectToRoute('app_admin_articles');
                }
                return $this->render('back_office/admin_article_form_add.html.twig', [
                    'formAdminArticle'  => $formAdminArticle->createView(),
                    'photoActuelle' => $article->getPhoto()

                ]);
        }

        /*
        Exo : affichage et suppression catégorie 
        1. Création d'une nouvelle route '/admin/categories' (name: app_admin_categories)
        2. Création d'une nouvelle méthode adminCategories()
        3. Création d'un nouveau template 'admin_categories.html.twig'
        4. Selectionner les noms des champs/colonnes de la table Category, les transmettre au template et les afficher 
        5. Selectionner dans le controller l'ensemble de la table 'category' (findAll) et transmettre au template (render) et les afficher sur le template (Twig),
           afficher également le nombre d'article liés à chaque catégorie
        6. Prévoir un lien 'modifier' et 'supprimer' pour chaque categorie
        7. Réaliser le traitement permettant de supprimer une catégorie de la BDD
    */

    #[Route('/admin/categories', name: 'app_admin_categorie')]
    public function adminCategories(CategoryRepository $repoCategory, EntityManagerInterface $manager)
    {

        $colonnes = $manager->getClassMetadata(Category::class)->getFieldNames();

        $allCategory = $repoCategory->findAll();


        return $this->render('back_office/admin_categories.html.twig',[
            'colonnes' => $colonnes,
            'allCategory' => $allCategory,
        ]);

    }
}


    
