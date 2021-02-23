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