<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleType;
use App\Form\CategoryType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
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
                    'photoActuelle' => $article->getPhoto(), // envoie la photo de l'article pour l'afficher en cas de modification
                    'editMode' => $article->getId()

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

    #[Route('/admin/categories', name: 'app_admin_categories')]
    #[Route('/admin/categorie/{id}/remove', name: 'app_admin_categorie_remove')]
    public function adminCategories(CategoryRepository $repoCategory, EntityManagerInterface $manager, Category $category = null):Response
    {

        $colonnes = $manager->getClassMetadata(Category::class)->getFieldNames();

        $allCategory = $repoCategory->findAll(); // SELECT * FROM CATEGORY + FETCH_ALL

        // dd($allCategory);

        if($category)
        {
            $titreCat = $category->getTitre();

            // getArticles() retourne tout les articles liés à la catégorie, si le résultat est 
            //vide,cela veut dire qu'aucun article n'est lié à la catégorie, on entre dans le IF et on supprime la catégorie
            if($category->getArticles()->isEmpty())
            {
                $this->addFlash('success', "La catégorie '$titreCat' a été supprimée avec succès.");
                
                $manager->remove($category);
                $manager->flush();
            }
            else // Sinon, des articles sont enore miés à la catégorie, alors on affiche un message d'erreur à l'utilisateur
            {
                $this->addFlash('danger', "Impossible de supprimer la catégorie '$titreCat' cra des articles y sont toujours associés.");
            }

            return $this->redirectToRoute('app_admin_categories');

        }


        return $this->render('back_office/admin_categories.html.twig',[
            'colonnes' => $colonnes,
            'allCategory' => $allCategory
        ]);

    }


    #[Route('/admin/categories/add', name: 'app_admin_categorie_add')]
    #[Route('/admin/categories/{id}/update', name: 'app_admin_categorie_update')]
    public function adminFormCategories(Request $request, EntityManagerInterface $manager, Category $category = null):Response
    {
        if(!$category)
            $category = new Category;

         $formCategory = $this->createForm(CategoryType::class, $category);

         $formCategory->handleRequest($request);

         if($formCategory->isSubmitted() && $formCategory->isValid())
         {
            //  dd($category);
            if($category->getId())
                $txt = 'modifiée';
            else
                $txt = 'enregistrée';

            $manager->persist($category);
            $manager->flush();

            // on stocke le titre de la categorie dans une variable afin de l'integrer dans el message de validation
            $titreCat = $category->getTitre();

            $this->addFlash('success', "La catégorie '$titreCat' a été $txt avec succès.");

            return $this->redirectToRoute('app_admin_categories');
        }



        return $this->render('back_office/admin_categorie_form.html.twig', [
            'formCategory' => $formCategory->createView(),
            'editMode' => $category->getId()
        ]);
    }


/*
    Exo: Affichage et suppression des commentaires
    1. Création d'une nouvelle route '/admin/commentaires' (name: app_admin_commentaires)
    2. Création d'une nouvelle méthode adminCommentaires()
    3. Création d'un nouveau template 'admin_commentaires.html.twig
    4. Sélectionner les noms/champs colonne de la table 'Comment' et les afficher sur le template
    5. Sélectionner l'ensemble de la table 'Comment' et afficher les données sous forme de tableau
    6. Mettre en place 'dataTable' pour pouvoir filtrer/rechercher des commentaires
    7. Créer une nouvelle route (sur la même méthode) '/admin/comment/{id}/remove' (name: app_admin_comment_remove)
    8. Réaliser le traitement permettant de supprimer un commentaire dans la BDD

*/

    #[Route('/admin/commentaires', name: 'app_admin_commentaires')]
    public function adminCommentaires(EntityManagerInterface $manager, CommentRepository $repoComment): response
    {
        // on sélectionne le nom des champs/colonnes
        $colonnes = $manager->getClassMetadata(Comment::class)->getFieldNames();
        dd($colonnes);

        $commentaires = $repoComment->findAll();
        dd($commentaires);
        

       return $this->render('back_office/admin_commentaires.html.twig',[
           'colonnes' => $colonnes

       ]);



















       
    }


}
