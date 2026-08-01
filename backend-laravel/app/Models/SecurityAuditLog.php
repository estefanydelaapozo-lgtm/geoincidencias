<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAuditLog extends Model
{
    protected $table = 'security_audit_logs';
    protected $fillable = ['evento','id_usuario','ip_origen','metodo','ruta','user_agent','contexto'];
    protected $casts = ['contexto' => 'array'];
}
