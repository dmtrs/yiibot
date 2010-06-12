CREATE TABLE "doc_cl_methods" ("me_class" VARCHAR NOT NULL , "me_name" VARCHAR NOT NULL , "me_returns" VARCHAR DEFAULT '-', "me_description" VARCHAR DEFAULT '-', "me_definedby" VARCHAR DEFAULT '-', "me_access" VARCHAR DEFAULT '-', "me_link" VARCHAR DEFAULT '-', PRIMARY KEY ("me_class", "me_name"));
CREATE TABLE "doc_cl_properties" ("pr_class" VARCHAR NOT NULL , "pr_name" VARCHAR NOT NULL , "pr_type" VARCHAR DEFAULT '-', "pr_description" VARCHAR DEFAULT '-', "pr_definedby" VARCHAR DEFAULT '-', "pr_access" VARCHAR DEFAULT '-', "pr_link" VARCHAR DEFAULT '-', PRIMARY KEY ("pr_class", "pr_name"));
CREATE TABLE "doc_class" ("cl_name" VARCHAR PRIMARY KEY  NOT NULL , "cl_package" VARCHAR DEFAULT '-', "cl_inheritance" VARCHAR DEFAULT '-', "cl_subclasses" VARCHAR DEFAULT '-', "cl_since" VARCHAR DEFAULT '-', "cl_version" VARCHAR DEFAULT '-', "cl_description" VARCHAR DEFAULT '-', "cl_link" VARCHAR DEFAULT '-');