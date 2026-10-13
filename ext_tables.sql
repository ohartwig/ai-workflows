CREATE TABLE tx_aiworkflows_workflow_instance (
    workflow_name varchar(255) DEFAULT '' NOT NULL,
    workflow_definition longtext,
    status varchar(50) DEFAULT 'pending' NOT NULL,
    current_step varchar(255) DEFAULT '' NOT NULL,
    context_data mediumtext,
    input_data longtext,
    output_data longtext,
    started_by int(11) DEFAULT '0' NOT NULL,
    started_at int(11) DEFAULT '0' NOT NULL,
    completed_at int(11) DEFAULT '0' NOT NULL,
    error_message text,

    KEY status (status),
    KEY started_by (started_by)
);

CREATE TABLE tx_aiworkflows_review_task (
    workflow_instance int(11) DEFAULT '0' NOT NULL,
    step_id varchar(255) DEFAULT '' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    content_data mediumtext,
    status varchar(50) DEFAULT 'pending' NOT NULL,
    reviewer int(11) DEFAULT '0' NOT NULL,
    review_comment text,
    deadline int(11) DEFAULT '0' NOT NULL,
    reviewed_at int(11) DEFAULT '0' NOT NULL,

    KEY status (status),
    KEY workflow_instance (workflow_instance),
    KEY deadline (deadline)
);

CREATE TABLE tx_aiworkflows_ai_task (
    workflow_instance int(11) DEFAULT '0' NOT NULL,
    step_id varchar(255) DEFAULT '' NOT NULL,
    task_type varchar(100) DEFAULT 'generate' NOT NULL,
    prompt text,
    context_data mediumtext,
    expected_schema text,
    response_data mediumtext,
    status varchar(50) DEFAULT 'pending' NOT NULL,
    created_at int(11) DEFAULT '0' NOT NULL,
    completed_at int(11) DEFAULT '0' NOT NULL,
    completed_by varchar(255) DEFAULT '' NOT NULL,

    KEY status (status),
    KEY workflow_instance (workflow_instance)
);
