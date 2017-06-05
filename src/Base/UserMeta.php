<?php
namespace Base;

use Aura\SqlQuery\QueryFactory;
use Goutte\Client;

class UserMeta
{
    /**
     *
     * @param int $telegram_id
     */
    public function getUser(int $telegram_id)
    {
        $query_factory = new QueryFactory(getenv('DB_ADAPTER'));
        $select = $query_factory->newSelect();
        $select->cols(['username', 'password', 'name'])
               ->from('userdata')
               ->where('telegram_id = :telegram_id')
               ->bindValue('telegram_id', $telegram_id);

        $db = DB::getInstance();
        $sth = $db->prepare($select->getStatement());
        $sth->execute($select->getBindValues());
        return $sth->fetch();
    }
    
    public function newUser(int $telegram_id, int $username, string $password = null)
    {
        $query_factory = new QueryFactory(getenv('DB_ADAPTER'));
        $insert = $query_factory->newInsert();
        $insert->into('userdata')
               ->cols(['telegram_id', 'username', 'password'])
               ->bindValues([
                   'telegram_id' => $telegram_id,
                   'username'    => $username,
                   'password'    => $password
               ]);

        $db = DB::getInstance();
        $sth = $db->prepare($insert->getStatement());
        return $sth->execute($insert->getBindValues());
    }
    
    public function updateUser(int $telegram_id, $data)
    {
        $query_factory = new QueryFactory(getenv('DB_ADAPTER'));
        $update = $query_factory->newUpdate();
        $update->table('userdata')
               ->where('telegram_id = :telegram_id')
               ->bindValue('telegram_id', $telegram_id);
        if(array_key_exists('username', $data)) {
            $update->col('username');
            $update->bindValue('username', $data['username']);
        }
        if(array_key_exists('password', $data)) {
            $update->col('password');
            $update->bindValue('password', $data['password']);
        }
        if(array_key_exists('name', $data)) {
            $update->col('name');
            $update->bindValue('name', $data['name']);
        }

        $db = DB::getInstance();
        $sth = $db->prepare($update->getStatement());
        return $sth->execute($update->getBindValues());
    }
    
    public function deleteUser(int $telegram_id)
    {
        $query_factory = new QueryFactory(getenv('DB_ADAPTER'));
        $update = $query_factory->newDelete();
        $update->from('userdata')
               ->where('telegram_id = :telegram_id')
               ->bindValues([
                   'telegram_id' => $telegram_id
               ]);

        $db = DB::getInstance();
        $sth = $db->prepare($update->getStatement());
        return $sth->execute($update->getBindValues());
    }
    
    public function validateUser($telegram_id, $username, $password)
    {
        $client = new Client();

        $guzzleClient = new \GuzzleHttp\Client(['timeout' => 60]);

        $client->setClient($guzzleClient);
        $response = $client->request('POST', 'http://sistacad.cederj.edu.br/inicio.asp', [
            'txtLogin'    => $username,
            'txtPassword' => $password
        ]);
        if(strpos($response->text(), 'Login ou senha inválidos!')) {
            $this->deleteUser($telegram_id);
            return false;
        }
        $cabecalhoinfotitulo = $response->filter('.cabecalhoinfotitulo')->text();
        preg_match('/\), (?<fullName>.*)/', $cabecalhoinfotitulo, $matches);
        $this->updateUser($telegram_id, [
            'name' => $matches['fullName']
        ]);
        return true;
    }
}