## Solr Workshop

### Gereksinimler
<pre>
  Java 1.6
  php => 5.3
  MySQL
</pre>

### Kurulum
    git clone git@github.com:phpistorg/workshop-eylul-2013.git
    cd workshop-eylul-2013
    cp app/config/parameters.yml.bak app/config/parameters.yml
    curl -s http://getcomposer.org/installer | php
    php composer.phar install

**parameters.yml** dosyasına göre veritabanı açarsanız dökümanın ilerleyen kısımlarında sorun yaşamazsınız.

### Solr'ı aktif hâle getirin.
    cd workshop-eylul-2013/solr/jetty
    java -jar start.jar

### Test edin
    http://localhost:8983/solr/#/workshop

### Örnek verileri yükleme
    mysql -uroot -proot workshop < workshop-eylul-2013/data/workshop.sql

### Dataların aktarımı
    php app/console solr:import full

### Sorgular
    http://localhost:8983/solr/workshop/browse?q=k%C4%B1rm%C4%B1z%C4%B1%20kol&wt=xml&fl=*,score&indent=true&facet.field=name&facet.field=name_2&facet.field=name_3&facet.field=name_4&facet.mincount=0

### Bunu mu demek istediniz ?

    http://localhost:8983/solr/workshop/browse?q=k%C4%B1rm%C4%B1&wt=xml&fl=*,score&indent=true

    http://localhost:8983/solr/workshop/browse?q=k%C4%B1rm%C4%B1%20ayakk&wt=xml&fl=*,score&indent=true&facet.field=text&facet.field=text_2&facet.mincount=1

    http://localhost:8983/solr/workshop/browse?q=k%C4%B1rm%C4%B1%20ayakka&wt=xml&fl=*,score&indent=true&facet.field=text&facet.field=text_2&facet.mincount=1

### Kelime kökü örneği
    http://localhost:8983/solr/workshop/select?q=name_3:k%C4%B1rm%C4%B1z%C4%B1&debugQuery=true&wt=xml&fl=*,score&indent=true

### Boost örnekleri;
(field da geçen tam kelime gerekli, dismax)

    http://localhost:8983/solr/workshop/select?defType=dismax&q=sakat&wt=xml&fl=*,score&indent=true&bq=category:saat^5.0
    http://localhost:8983/solr/workshop/select?defType=dismax&q=sakat&wt=xml&fl=*,score&indent=true&bq=category:%C3%A7izme^5.0
    http://localhost:8983/solr/workshop/select?defType=edismax&q=sakat&wt=xml&fl=*,score&indent=true&boost=score_product_boost

    http://localhost:8983/solr/workshop/select?defType=edismax&q=sakat&wt=xml&fl=*,score&sort=sum(product(0.9,score_product_boost),product(0.0,score_product_boost_2))+desc
    http://localhost:8983/solr/workshop/select?defType=edismax&q=sakat&wt=xml&fl=*,score&sort=sum(product(0.9,score_product_boost),product(0.1,score_product_boost_2))+desc

(score değerlerine dikkat)

    http://localhost:8983/solr/workshop/select?defType=edismax&q=sa*&wt=xml&fl=*,score&indent=true&qf=name









