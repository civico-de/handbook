<div class="crm-container handbook">
  {include file="CRM/Handbook/chrome.tpl"}
  <div class="handbook-layout">
    {include file="CRM/Handbook/toc.tpl"}
    <section class="handbook-results" aria-labelledby="handbook-results-heading">
      <h2 id="handbook-results-heading">{ts domain="handbook"}Search results{/ts}</h2>
      {if $handbookTerm eq ''}
        <p>{ts domain="handbook"}Please enter a search term.{/ts}</p>
      {elseif $handbookResults}
        {capture assign=handbookSectionsText}{ts domain="handbook" count=$handbookSectionCount plural="%count sections"}One section{/ts}{/capture}
        {capture assign=handbookPagesText}{ts domain="handbook" count=$handbookResults|@count plural="%count pages"}one page{/ts}{/capture}
        <p role="status">{ts domain="handbook" 1=$handbookSectionsText 2=$handbookPagesText 3=$handbookTerm|escape}%1 on %2 for “%3”{/ts}</p>
        <ol class="handbook-result-pages">
          {foreach from=$handbookResults item=result}
            <li>
              <h3><a href="{$result.url|escape}">{$result.title|escape}</a></h3>
              <p class="handbook-result-place">{$result.place|escape}</p>
              <ul class="handbook-hits">
                {foreach from=$result.hits item=hit}
                  <li>
                    <a href="{$hit.url|escape}">{if $hit.heading}{$hit.heading|escape}{else}{$result.title|escape}{/if}</a>
                    <p class="handbook-context-line">{$hit.context|escape}</p>
                  </li>
                {/foreach}
              </ul>
              {if $result.more}
                <p class="handbook-more">{ts domain="handbook" count=$result.more plural="and %count more sections"}and one more section{/ts}</p>
              {/if}
            </li>
          {/foreach}
        </ol>
      {else}
        <p role="status">{ts domain="handbook" 1=$handbookTerm|escape}No results for “%1”.{/ts}</p>
      {/if}
    </section>
  </div>
</div>
