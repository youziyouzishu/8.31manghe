create table if not exists oplog_operation_log
(
    id            bigint auto_increment,
    username      varchar(32)  not null comment '用户名',
    method        varchar(20)  not null comment '请求方式',
    router        varchar(500) not null comment '路由',
    ip            varchar(32)  not null comment 'IP',
    request_data  text         null comment '请求数据',
    response_data text         null comment '响应数据',
    operation_log longtext     null comment '操作日志',
    created_at    datetime     not null comment '创建时间',
    constraint export_database_pk
    primary key (id),
    key oplog_operation_log_username_index (username)
    )
    comment '操作日志';