<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 12:31 PM
 */

declare(strict_types=1);

class ClientManager implements ServiceInterface {
    private static $token;
    private static $sandbox;
    private static $china;
    private static $client;

    private static function buildClient() {
        static::$token = Conf::mustGet('client.token');
        static::$sandbox = Conf::getBool('client.sandbox', true);
        static::$china = Conf::getBool('client.china', false);

        static::$client = new Evernote\Client(static:: $token, static:: $sandbox, null, null, static:: $china);
    }

    public static function get(): \Evernote\Client {
        return static::$client;
    }

    public static function init(): bool {
        static::buildClient();

        $clt = self::get();
        $note = new \Evernote\Model\Note();
        $note->setTitle("abc");
        $note->setContent(new \Evernote\Model\PlainTextNoteContent("this is content"));
        $note->setTagNames(['abc', 'qqzone']);
        $clt->uploadNote($note, null);

        $noteStore = $clt->getUserNotestore();
        $syncState = $noteStore->getSyncState(static::$token);

        $syncState->updateCount;
        $noteFilter = new \EDAM\NoteStore\NoteFilter();
        $noteMetaResultSpec = new \EDAM\NoteStore\NotesMetadataResultSpec([
            'includeTitle' => true,
            'includeContentLength' => true,
            'includeCreated' => true,
            'includeUpdated' => true,
            'includeDeleted' => true,
            'includeUpdateSequenceNum' => true,
            'includeNotebookGuid' => true,
            'includeTagGuids' => true,
            'includeAttributes' => true,
            'includeLargestResourceMime' => true,
            'includeLargestResourceSize' => true,
        ]);
        $newNotes = $noteStore->findNotesMetadata(static::$token, $noteFilter, 0, 100, $noteMetaResultSpec);

        foreach ($newNotes as $note) {
            var_dump($note);
        }

        return true;
    }
}