1.2.0  
  - prepareDataProvider callback for ListAction moved in better place (fix [#7](https://github.com/Insolita/yii2-fractal/pull/8))
  - added prepareDataProvider callback for ViewRelationshipsAction
  
1.2.2 
  - add support for joinWith queries;
  
1.2.3
  - provide before run and after run action events
  
1.2.4
 - Add a callback for validate ids to (create/update/delete)Relationships actions
 - fix IdOnlyTransformer

1.3.0
 - ListAction And ViewRelationshipAction requested with HEAD method now return headers with pagination info, same 
   like yii rest 
    - X-Pagination-Total-Count
    - X-Pagination-Page-Count
    - X-Pagination-Current-Page
    - X-Pagination-Per-Page
   
 - new CountAction that can count query without loading data. Return X-Pagination-Total-Count with count result

 - new ListForIdentityAction and ViewForIdentityAction for show data related to current user

1.3.1
  - Add CountForIdentityAction
  - remove includes from counter actions

1.3.2
  - Add validator callback for ability to check allowedRelations for Create and Update Actions

1.3.3
 - Add support scenario for delete action

1.3.4
 - Add aftersave callback for create/update actions that called after save model with all relations

1.3.5
 - Change UpdateAction, set model scenario before checkAccess calling

1.4.0
 - Add callable afterDelete for DeleteAction (#17)
 - Add additional param originalModel for afterSave callback for UpdateAction (#18)

1.4.1 
 - fix #19  merge (#20) Thanks [Sohel Ahmed](https://github.com/SOHELAHMED7)