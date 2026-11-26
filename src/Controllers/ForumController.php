<?php declare(strict_types=1);
namespace Sunset\Controllers;
use Sunset\Core\View; use Sunset\Helpers\Auth; use Sunset\Helpers\Csrf; use Sunset\Helpers\Flash;
use Sunset\Models\Book; use Sunset\Models\Forum; use Sunset\Models\ForumMessage;

final class ForumController {
    public function index(): void {
        Auth::requireLogin();
        $q=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,100):'';
        $tab=(($_GET['tab']??'')==='mine')?'mine':'explore';
        $forums=$q!==''?Forum::search($q):($tab==='mine'?Forum::myForums((int)Auth::id()):Forum::all());
        View::render('forum/index',['title'=>'Foros','forums'=>$forums,'q'=>$q,'tab'=>$tab]);
    }

    public function show(): void {
        Auth::requireLogin();
        $id=(int)($_GET['id']??0); $forum=$id>0?Forum::findById($id):null;
        if(!$forum){http_response_code(404);View::render('forum/not_found',['title'=>'Foro no encontrado']);return;}
        $role=Forum::memberRole($id,(int)Auth::id());
        $messages=ForumMessage::recent($id,50);
        $members=Forum::members($id);
        View::render('forum/show',['title'=>$forum['name'],'forum'=>$forum,'role'=>$role,'messages'=>$messages,'members'=>$members,'scripts'=>['js/chat.js']]);
    }

    public function create(): void {
        Auth::requireLogin();
        $bookId=(int)($_GET['book_id']??0); $book=$bookId>0?Book::findById($bookId):null;
        View::render('forum/create',['title'=>'Crear foro','book'=>$book]);
    }

    public function store(): void {
        Auth::requireLogin(); Csrf::verify();
        $name=mb_substr(trim(input('name')),0,100);
        $desc=mb_substr(trim(input('description')),0,300);
        $bookId=(int)input('book_id'); $book=$bookId>0?Book::findById($bookId):null;
        if(mb_strlen($name)<3){Flash::add('danger','El nombre debe tener al menos 3 caracteres.');redirect('forum_create',['book_id'=>$bookId]);return;}
        $id=Forum::create((int)Auth::id(),$name,$desc,$book?$bookId:null);
        Flash::add('success','Foro creado.'); redirect('forum',['id'=>$id]);
    }

    public function edit(): void {
        Auth::requireLogin(); Csrf::verify();
        $id=(int)input('forum_id'); $forum=Forum::findById($id);
        if(!$forum||Forum::memberRole($id,(int)Auth::id())!=='admin'){redirect('forums');return;}
        Forum::update($id,mb_substr(trim(input('name')),0,100),mb_substr(trim(input('description')),0,300));
        Flash::add('success','Foro actualizado.'); redirect('forum',['id'=>$id]);
    }

    public function delete(): void {
        Auth::requireLogin(); Csrf::verify();
        $id=(int)input('forum_id'); $forum=Forum::findById($id);
        if($forum&&Forum::memberRole($id,(int)Auth::id())==='admin'){
            Forum::delete($id); Flash::add('success','Foro eliminado.');
        }
        redirect('forums');
    }

    public function join(): void {
        Auth::requireLogin(); Csrf::verify();
        $id=(int)input('forum_id'); $action=input('action');
        $forum=Forum::findById($id);
        if(!$forum){redirect('forums');return;}
        $role=Forum::memberRole($id,(int)Auth::id());
        if($action==='leave'&&$role!=='admin') Forum::leave($id,(int)Auth::id());
        elseif($action==='join'&&$role===null) Forum::join($id,(int)Auth::id());
        $ref=input('return_to');
        preg_match('/^book:(\d+)$/',$ref,$m)?redirect('book',['id'=>(int)$m[1]]):redirect('forum',['id'=>$id]);
    }

    public function kick(): void {
        Auth::requireLogin(); Csrf::verify();
        $forumId=(int)input('forum_id'); $userId=(int)input('user_id');
        if(Forum::memberRole($forumId,(int)Auth::id())==='admin'&&$userId!==(int)Auth::id())
            Forum::kick($forumId,$userId);
        redirect('forum',['id'=>$forumId]);
    }

    public function messages(): void {
        if(!Auth::check()){json_response(['error'=>'No autorizado'],401);}
        $id=(int)($_GET['forum_id']??0); $after=(int)($_GET['after']??0);
        $msgs=ForumMessage::since($id,$after);
        $html=''; foreach($msgs as $m) $html.=View::partial('partials/chat_message',['msg'=>$m,'myId'=>Auth::id(),'forumId'=>$id]);
        json_response(['html'=>$html,'last'=>$msgs?((int)end($msgs)['id']):$after]);
    }

    public function send(): void {
        if(!Auth::check()){json_response(['error'=>'No autorizado'],401);} Csrf::verify();
        $id=(int)input('forum_id'); $body=mb_substr(trim(input('body')),0,2000);
        if($body===''){json_response(['error'=>'El mensaje no puede estar vacío.'],422);}
        $role=Forum::memberRole($id,(int)Auth::id());
        if($role===null){json_response(['error'=>'Debes unirte al foro para escribir.'],403);}
        $msg=ForumMessage::add($id,(int)Auth::id(),$body);
        $html=View::partial('partials/chat_message',['msg'=>$msg,'myId'=>Auth::id(),'forumId'=>$id]);
        json_response(['html'=>$html,'id'=>(int)$msg['id']]);
    }

    public function deleteMessage(): void {
        Auth::requireLogin(); Csrf::verify();
        $msgId=(int)input('msg_id'); $forumId=(int)input('forum_id');
        $role=Forum::memberRole($forumId,(int)Auth::id());
        if($role==='admin') ForumMessage::delete($msgId,$forumId);
        json_response(['ok'=>true]);
    }
}
