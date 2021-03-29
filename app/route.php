<?php
// https://www.dookay.com/insight
// https://bluestag.co.uk/about/
// https://www.c9-d.com/news/

return [    
  '__pattern__' => [
    'mark'  =>  '\w+',
    'id'    =>  '\d+',
    'page'  =>  '\d+',
  ],
  
  '/'                     => 'index/index/index',     //首页
  'search$'                => 'index/page/search',      //搜索
  'about$'                => 'index/page/about',      //青桐品牌
  'brand$'                => 'index/page/brand',      //青桐品牌
  'contact$'              => 'index/page/contact',    //联系
  'case/:id$'             => ['index/product/detail', [], ['id' => '\d+']],  //案例详情
  'case/[:mark]$'         => 'index/product/index',   //案例列表
  'news/:id$'             => ['index/news/detail', [], ['id' => '\d+']],  //新闻详情
  'news/[:mark]$'         => 'index/news/index',   //新闻列表
  '404$'                  => 'index/error/404',       //404错误
];


