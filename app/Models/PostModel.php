<?php

namespace App\Models;

use CodeIgniter\Model;

class PostModel extends Model
{
    protected $table = 'post';
    protected $primaryKey = 'id_post';

    public function getPostUser($id_user)
    {
        $builder = $this->db->table('post a');
        $builder->select('a.*, b.nama_guru, b.foto, d.nama_panitia, d.foto as foto_panitia, (SELECT COUNT(post_comments.id_comment) FROM post_comments WHERE a.id_post = post_comments.id_post) AS jml');
        $builder->join('master_guru b', 'a.dari=b.id_guru', 'left');
        $builder->join('master_panitia d', 'a.dari=d.id_panitia', 'left');
        if ($id_user != 0) {
            $builder->where('a.dari', $id_user);
        }
        $builder->orderBy('a.updated', 'desc');
        return $builder->get()->getResult();
    }

    public function getPostForUser($kepada, $kelas = null, $limit = null, $offset = null)
    {
        $builder = $this->db->table('post a');
        $builder->select('a.*, b.nama_guru, b.foto, (SELECT COUNT(post_comments.id_comment) FROM post_comments WHERE a.id_post = post_comments.id_post) AS jml');
        $builder->join('master_guru b', 'a.dari=b.id_guru', 'left');

        // If kepada is '0', show all posts (for admin)
        // Otherwise, filter by kepada and kelas
        if ($kepada !== '0' && $kepada !== 0 && $kepada !== null) {
            // Remove quotes from kepada if present (e.g., "'siswa'" -> "siswa")
            $kepadaClean = trim($kepada, "'\"");

            // Build proper LIKE conditions for JSON array search
            $whereClause = "(a.kepada LIKE '%" . $this->db->escapeLikeString($kepadaClean) . "%')";

            // Add kelas condition if kelas is valid (not 0 or null)
            if ($kelas && $kelas != 0) {
                $whereClause .= " OR (a.kepada LIKE '%" . $this->db->escapeLikeString($kelas) . "%')";
            }

            $builder->where($whereClause);
        }
        // If kepada is '0' (admin), no filter is applied - show all posts

        $builder->orderBy('a.updated', 'desc');
        if ($limit != null) {
            $builder->limit($limit, $offset);
        }
        return $builder->get()->getResult();
    }

    public function getIdComments($id_post)
    {
        $builder = $this->db->table('post_comments');
        $builder->select('id_comment');
        $builder->where("id_post", $id_post);
        return $builder->get()->getResult();
    }

    public function getIdReplies($id_comment)
    {
        $builder = $this->db->table('post_reply');
        $builder->select('id_reply');
        if (is_array($id_comment)) {
            $builder->whereIn("id_comment", $id_comment);
        } else {
            $builder->where("id_comment", $id_comment);
        }
        return $builder->get()->getResult();
    }
}
