-- Find duplicate index entries
select location_id, sample_id, count(*)
into temporary to_cleanup
from index_locations_samples ils
group by location_id, sample_id
having count(*) > 1;

-- clean them up
delete from index_locations_samples where id in (
  select hi.id
  from to_cleanup cl
  join index_locations_samples lo on lo.sample_id=cl.sample_id and lo.location_id=cl.location_id
  join index_locations_samples hi on hi.sample_id=cl.sample_id and hi.location_id=cl.location_id
  where lo.id<hi.id
);