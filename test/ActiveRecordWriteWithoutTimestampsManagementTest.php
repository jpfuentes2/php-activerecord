<?php

use ActiveRecord\DateTime;

class ActiveRecordWriteWithoutTimestampsManagementTest extends DatabaseTest
{
    public function set_up($connection_name=null)
    {
        if (true === ActiveRecord\ConnectionManager::get_connection()->get_timestamps_management()) {
            ActiveRecord\ConnectionManager::drop_connection(ActiveRecord\Config::instance()->get_default_connection());
            ActiveRecord\Config::instance()->set_timestamps_management(false);
        }

        parent::set_up($connection_name);
    }

    public function tear_down()
    {
        ActiveRecord\ConnectionManager::drop_connection(ActiveRecord\Config::instance()->get_default_connection());
        ActiveRecord\Config::instance()->set_timestamps_management(true);
    }

    public function test_timestamps_not_set_before_save_with_turned_off_timestamps_management()
    {
        $author = new Author;
        $this->assert_false($author->connection()->get_timestamps_management());
        $author->save();
        $this->assert_null($author->created_at, $author->updated_at);

        $author->reload();
        $this->assert_null($author->created_at, $author->updated_at);
    }

    public function test_timestamps_after_reloading_with_turned_off_timestamps_management()
    {
        $author = Author::create(array());
        $this->assert_false($author->connection()->get_timestamps_management());
        $this->assert_null($author->created_at, $author->updated_at);

        $clonedAuthor = Author::find($author->id);
        $clonedAuthor->update_attributes(array(
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ));

        $author->reload();
        $this->assert_not_null($author->created_at, $author->updated_at);

        $updated_at = $author->updated_at;
        $author->name = 'test';
        $author->save();

        $this->assert_equals($updated_at, $author->updated_at);

        sleep(1);
        $clonedAuthor->update_attributes(array(
            'updated_at' => new DateTime(),
        ));

        $author->reload();
        $this->assert_not_equals($updated_at, $author->updated_at);
    }
}