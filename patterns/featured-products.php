<?php
/**
 * Title: New products
 * Slug: 3ducation/featured-products
 * Categories: 3ducation, woocommerce
 * Description: A row of the newest products from the catalog.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Nieuw binnen</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"textDecoration":"underline"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="text-decoration:underline"><a href="/shop">Bekijk alles</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:woocommerce/product-collection {"queryId":1,"query":{"perPage":4,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"align":"wide","displayLayout":{"type":"flex","columns":4}} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","style":{"border":{"radius":"10px"}}} /-->
<!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"fontSize":"medium","style":{"spacing":{"margin":{"bottom":"0.25rem"}}}} /-->
<!-- wp:woocommerce/product-price /-->
<!-- wp:woocommerce/product-button {"textAlign":"left"} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->
